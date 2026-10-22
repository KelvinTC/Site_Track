<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Expense::with(['category', 'department', 'siteJob', 'costCentre']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by cost centre
        if ($request->has('cost_centre_id')) {
            $query->where('cost_centre_id', $request->cost_centre_id);
        }

        // Filter by job
        if ($request->has('site_job_id')) {
            $query->where('site_job_id', $request->site_job_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('expense_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('expense_date', '<=', $request->to_date);
        }

        $expenses = $query->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($expenses);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'cost_centre_id' => 'nullable|exists:cost_centres,id',
            'department_id' => 'nullable|exists:departments,id',
            'site_job_id' => 'nullable|exists:site_jobs,id',
            'expense_date' => 'required|date',
            'description' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank_transfer,check,credit_card,mobile_money',
            'status' => 'in:pending,approved,paid,rejected',
            'notes' => 'nullable|string',
        ]);

        $expense = Expense::create($validated);
        $expense->load(['category', 'department', 'siteJob', 'costCentre']);

        return response()->json($expense, 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        $expense->load(['category', 'department', 'siteJob', 'costCentre']);

        return response()->json($expense);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $validated = $request->validate([
            'expense_category_id' => 'sometimes|required|exists:expense_categories,id',
            'cost_centre_id' => 'nullable|exists:cost_centres,id',
            'department_id' => 'nullable|exists:departments,id',
            'site_job_id' => 'nullable|exists:site_jobs,id',
            'expense_date' => 'sometimes|required|date',
            'description' => 'sometimes|required|string|max:255',
            'reference' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank_transfer,check,credit_card,mobile_money',
            'status' => 'in:pending,approved,paid,rejected',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);
        $expense->load(['category', 'department', 'siteJob', 'costCentre']);

        return response()->json($expense);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully']);
    }

    public function updateStatus(Request $request, Expense $expense): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,paid,rejected',
        ]);

        $expense->update($validated);
        $expense->load(['category', 'department', 'siteJob', 'costCentre']);

        return response()->json($expense);
    }

    public function summary(Request $request): JsonResponse
    {
        $query = Expense::query();

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('expense_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('expense_date', '<=', $request->to_date);
        }

        // Summary by category
        $byCategory = (clone $query)
            ->selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')
            ->with('category')
            ->get()
            ->map(fn($e) => [
                'category' => $e->category->name ?? 'Uncategorized',
                'total' => (float) $e->total,
            ]);

        // Summary by department
        $byDepartment = (clone $query)
            ->whereNotNull('department_id')
            ->selectRaw('department_id, SUM(amount) as total')
            ->groupBy('department_id')
            ->with('department')
            ->get()
            ->map(fn($e) => [
                'department' => $e->department->name ?? 'Unknown',
                'total' => (float) $e->total,
            ]);

        // Summary by cost centre
        $byCostCentre = (clone $query)
            ->whereNotNull('cost_centre_id')
            ->where('status', 'approved')
            ->selectRaw('cost_centre_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('cost_centre_id')
            ->with('costCentre')
            ->get()
            ->map(fn($e) => [
                'id' => $e->cost_centre_id,
                'code' => $e->costCentre->code ?? '',
                'name' => $e->costCentre->name ?? 'Unknown',
                'budget' => (float) ($e->costCentre->budget ?? 0),
                'spent' => (float) $e->total,
                'remaining' => (float) (($e->costCentre->budget ?? 0) - $e->total),
                'count' => $e->count,
            ]);

        // Summary by status
        $byStatus = (clone $query)
            ->selectRaw('status, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Total
        $total = (clone $query)->sum('amount');

        return response()->json([
            'total' => (float) $total,
            'by_category' => $byCategory,
            'by_department' => $byDepartment,
            'by_cost_centre' => $byCostCentre,
            'by_status' => $byStatus,
        ]);
    }
}
