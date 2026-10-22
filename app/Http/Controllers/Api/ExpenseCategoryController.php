<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExpenseCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ExpenseCategory::withCount('expenses')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20|unique:expense_categories,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category = ExpenseCategory::create($validated);

        return response()->json($category, 201);
    }

    public function show(ExpenseCategory $expenseCategory): JsonResponse
    {
        $expenseCategory->loadCount('expenses');

        return response()->json($expenseCategory);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:20|unique:expense_categories,code,' . $expenseCategory->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $expenseCategory->update($validated);

        return response()->json($expenseCategory);
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        if ($expenseCategory->expenses()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with existing expenses'
            ], 422);
        }

        $expenseCategory->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
