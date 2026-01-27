<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SiteJobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SiteJob::with(['customer', 'invoices']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $jobs = $query->orderBy('created_at', 'desc')->get();

        return response()->json($jobs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:new,in_progress,on_hold,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_cost' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'site_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $job = SiteJob::create($validated);
        $job->load('customer');

        return response()->json($job, 201);
    }

    public function show(SiteJob $siteJob): JsonResponse
    {
        $siteJob->load(['customer', 'invoices.payments', 'invoices.items']);

        return response()->json($siteJob);
    }

    public function update(Request $request, SiteJob $siteJob): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|required|exists:customers,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:new,in_progress,on_hold,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_cost' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'site_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $siteJob->update($validated);
        $siteJob->load('customer');

        return response()->json($siteJob);
    }

    public function destroy(SiteJob $siteJob): JsonResponse
    {
        $siteJob->delete();

        return response()->json(['message' => 'Job deleted successfully']);
    }

    public function updateStatus(Request $request, SiteJob $siteJob): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:new,in_progress,on_hold,completed,cancelled',
        ]);

        $siteJob->update(['status' => $validated['status']]);

        return response()->json($siteJob);
    }
}
