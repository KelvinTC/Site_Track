<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Casual;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CasualController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Casual::withCount('siteJobs');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $casuals = $query->orderBy('created_at', 'desc')->get();

        return response()->json($casuals);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $casual = Casual::create($validated);

        return response()->json($casual, 201);
    }

    public function show(Casual $casual): JsonResponse
    {
        $casual->load(['siteJobs.customer']);
        $casual->loadCount('siteJobs');

        return response()->json($casual);
    }

    public function update(Request $request, Casual $casual): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $casual->update($validated);

        return response()->json($casual);
    }

    public function destroy(Casual $casual): JsonResponse
    {
        $casual->delete();

        return response()->json(['message' => 'Casual deleted successfully']);
    }

    public function jobsReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $query = Casual::with(['siteJobs' => function ($q) use ($validated) {
            if (!empty($validated['from_date'])) {
                $q->wherePivot('work_date', '>=', $validated['from_date']);
            }
            if (!empty($validated['to_date'])) {
                $q->wherePivot('work_date', '<=', $validated['to_date']);
            }
            $q->with('customer');
        }]);

        $casuals = $query->get()->map(function ($casual) {
            return [
                'id' => $casual->id,
                'casual_number' => $casual->casual_number,
                'name' => $casual->name,
                'job_title' => $casual->job_title,
                'phone' => $casual->phone,
                'is_active' => $casual->is_active,
                'jobs_count' => $casual->siteJobs->count(),
                'jobs' => $casual->siteJobs->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'job_number' => $job->job_number,
                        'title' => $job->title,
                        'customer_name' => $job->customer?->name,
                        'work_date' => $job->pivot->work_date,
                        'notes' => $job->pivot->notes,
                    ];
                }),
            ];
        });

        return response()->json($casuals);
    }
}
