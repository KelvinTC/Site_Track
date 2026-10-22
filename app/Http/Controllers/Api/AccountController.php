<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $query = Account::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return $query->orderBy('code')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:accounts',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'sub_type' => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Set level based on parent
        if (!empty($validated['parent_id'])) {
            $parent = Account::find($validated['parent_id']);
            $validated['level'] = ($parent->level ?? 0) + 1;

            // Mark parent as parent
            $parent->update(['is_parent' => true]);
        }

        $account = Account::create($validated);

        return response()->json($account, 201);
    }

    public function show(Account $account)
    {
        return $account;
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:accounts,code,' . $account->id,
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'sub_type' => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Set level based on parent
        if (!empty($validated['parent_id'])) {
            $parent = Account::find($validated['parent_id']);
            $validated['level'] = ($parent->level ?? 0) + 1;
        } else {
            $validated['level'] = 0;
        }

        $account->update($validated);

        return response()->json($account);
    }

    public function destroy(Account $account)
    {
        // Check if account has journal entry lines
        if ($account->journalEntryLines()->exists()) {
            return response()->json(['message' => 'Cannot delete account with journal entries'], 422);
        }

        // Check if account has children
        if ($account->children()->exists()) {
            return response()->json(['message' => 'Cannot delete account with child accounts'], 422);
        }

        $account->delete();
        return response()->json(null, 204);
    }
}
