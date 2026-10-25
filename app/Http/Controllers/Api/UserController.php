<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->current_company_id;

        $query = User::whereHas('companies', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->with(['companies' => function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        }]);

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        $query->orderBy('name');

        // Support pagination
        $perPage = $request->input('per_page', 15);
        if ($request->has('page')) {
            $paginated = $query->paginate($perPage);

            $formatted = collect($paginated->items())->map(function ($u) {
                $pivot = $u->companies->first()?->pivot;
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'is_active' => $u->is_active,
                    'role' => $pivot?->role,
                    'is_default' => $pivot?->is_default,
                    'created_at' => $u->created_at,
                ];
            });

            return response()->json([
                'data' => $formatted,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ]);
        }

        $users = $query->get();
        $formatted = $users->map(function ($u) {
            $pivot = $u->companies->first()?->pivot;
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'is_active' => $u->is_active,
                'role' => $pivot?->role,
                'is_default' => $pivot?->is_default,
                'created_at' => $u->created_at,
            ];
        });

        return response()->json(['data' => $formatted]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:owner,admin,finance,member',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
        ]);

        $companyId = $request->user()->current_company_id;

        // Attach user to current company with role
        $user->companies()->attach($companyId, [
            'role' => $validated['role'],
            'is_default' => true,
        ]);

        // Set their current company
        $user->current_company_id = $companyId;
        $user->save();

        AuditLog::record('created', $user, null, [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $validated['role'],
        ], $companyId, $this->auditLabel($user));

        return response()->json([
            'message' => 'User created successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role' => $validated['role'],
            ],
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $companyId = $request->user()->current_company_id;

        // Ensure the user belongs to the current company
        if (!$user->belongsToCompany($request->user()->currentCompany)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $pivot = $user->companies()->where('company_id', $companyId)->first()?->pivot;

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role' => $pivot?->role,
                'is_default' => $pivot?->is_default,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $companyId = $request->user()->current_company_id;

        // Ensure the user belongs to the current company
        if (!$user->belongsToCompany($request->user()->currentCompany)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'role' => 'sometimes|in:owner,admin,finance,member',
        ]);

        $before = $this->auditSnapshot($user, $companyId);

        // Update user fields
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        if (array_key_exists('phone', $validated)) {
            $user->phone = $validated['phone'];
        }
        if (isset($validated['is_active'])) {
            $user->is_active = $validated['is_active'];
        }
        $user->save();

        // Update role in pivot table
        if (isset($validated['role'])) {
            $user->companies()->updateExistingPivot($companyId, [
                'role' => $validated['role'],
            ]);
        }

        $pivot = $user->companies()->where('company_id', $companyId)->first()?->pivot;

        $after = $this->auditSnapshot($user, $companyId);
        $changed = array_keys(array_diff_assoc(array_map('strval', $after), array_map('strval', $before)));
        if (isset($validated['password'])) {
            // Record that the password changed, never the password itself
            $before['password_changed'] = false;
            $after['password_changed'] = true;
            $changed[] = 'password_changed';
        }
        if ($changed) {
            $keep = array_flip($changed);
            AuditLog::record('updated', $user, array_intersect_key($before, $keep), array_intersect_key($after, $keep), $companyId, $this->auditLabel($user));
        }

        return response()->json([
            'message' => 'User updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role' => $pivot?->role,
            ],
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();
        $companyId = $currentUser->current_company_id;

        // Ensure the user belongs to the current company
        if (!$user->belongsToCompany($currentUser->currentCompany)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Prevent self-deletion
        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'You cannot remove yourself from the company'], 400);
        }

        AuditLog::record('deleted', $user, $this->auditSnapshot($user, $companyId), null, $companyId, $this->auditLabel($user));

        // Detach user from company (don't delete the user)
        $user->companies()->detach($companyId);

        // If user's current company was this one, clear it
        if ($user->current_company_id === $companyId) {
            $newDefault = $user->companies()->first();
            $user->current_company_id = $newDefault?->id;
            $user->save();
        }

        return response()->json([
            'message' => 'User removed from company successfully',
        ]);
    }

    protected function auditSnapshot(User $user, int $companyId): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'role' => $user->companies()->where('company_id', $companyId)->first()?->pivot?->role,
        ];
    }

    protected function auditLabel(User $user): string
    {
        return "{$user->name} ({$user->email})";
    }
}
