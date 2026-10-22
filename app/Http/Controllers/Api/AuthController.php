<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'required|string|max:255',
        ]);

        $user = DB::transaction(function () use ($validated) {
            // Create the user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
            ]);

            // Create the company
            $company = Company::create([
                'name' => $validated['company_name'],
                'email' => $validated['email'],
                'is_active' => true,
            ]);

            // Attach user to company as owner
            $user->companies()->attach($company->id, [
                'role' => 'owner',
                'is_default' => true,
            ]);

            // Set current company
            $user->current_company_id = $company->id;
            $user->save();

            return $user;
        });

        $token = $user->createToken('default')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $this->formatUser($user->fresh()),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // Set current company if not set
        if (!$user->current_company_id) {
            $defaultCompany = $user->companies()->wherePivot('is_default', true)->first();
            if (!$defaultCompany) {
                $defaultCompany = $user->companies()->first();
            }
            if ($defaultCompany) {
                $user->current_company_id = $defaultCompany->id;
                $user->save();
            }
        }

        // Delete old tokens (optional - allows only one active session per device)
        // $user->tokens()->where('name', $request->device_name ?? 'default')->delete();

        $token = $user->createToken($request->device_name ?? 'default')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('currentCompany');

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    public function switchCompany(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $user = $request->user();
        $company = Company::find($request->company_id);

        if (!$user->belongsToCompany($company)) {
            return response()->json([
                'message' => 'You do not have access to this company.',
            ], 403);
        }

        $user->switchCompany($company);

        return response()->json([
            'message' => 'Company switched successfully',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    public function companies(Request $request): JsonResponse
    {
        $user = $request->user();
        $companies = $user->companies()->get()->map(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'role' => $company->pivot->role,
                'is_default' => $company->pivot->is_default,
            ];
        });

        return response()->json([
            'companies' => $companies,
        ]);
    }

    protected function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'current_company_id' => $user->current_company_id,
            'current_company' => $user->currentCompany ? [
                'id' => $user->currentCompany->id,
                'name' => $user->currentCompany->name,
                'slug' => $user->currentCompany->slug,
            ] : null,
            'role' => $user->getRoleForCompany(),
            'companies' => $user->companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'role' => $company->pivot->role,
                    'is_default' => $company->pivot->is_default,
                ];
            }),
        ];
    }
}
