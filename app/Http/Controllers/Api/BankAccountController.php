<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        return BankAccount::with('glAccount')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_type' => 'in:checking,savings,credit_card,money_market',
            'account_number' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric',
            'gl_account_id' => 'nullable|company_exists:accounts,id',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $bankAccount = BankAccount::create($validated);

        return response()->json($bankAccount->load('glAccount'), 201);
    }

    public function show(BankAccount $bankAccount)
    {
        return $bankAccount->load('glAccount');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_type' => 'in:checking,savings,credit_card,money_market',
            'account_number' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric',
            'gl_account_id' => 'nullable|company_exists:accounts,id',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $bankAccount->update($validated);

        return response()->json($bankAccount->load('glAccount'));
    }

    public function destroy(BankAccount $bankAccount)
    {
        if ($bankAccount->transactions()->exists()) {
            return response()->json(['message' => 'Cannot delete bank account with transactions'], 422);
        }

        $bankAccount->delete();
        return response()->json(null, 204);
    }

    public function recentTransactions()
    {
        return BankTransaction::with('bankAccount')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
    }
}
