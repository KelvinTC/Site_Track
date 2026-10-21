<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteJob;
use App\Models\Casual;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CasualSiteJobController extends Controller
{
    public function index(SiteJob $siteJob): JsonResponse
    {
        $casuals = $siteJob->casuals()
            ->select('casuals.*')
            ->get();

        return response()->json($casuals);
    }

    public function store(Request $request, SiteJob $siteJob): JsonResponse
    {
        $validated = $request->validate([
            'casual_ids' => 'required|array',
            'casual_ids.*' => 'exists:casuals,id',
            'work_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $pivotData = [
            'assigned_date' => now()->toDateString(),
            'work_date' => $validated['work_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        foreach ($validated['casual_ids'] as $casualId) {
            // Avoid duplicate entries for same casual, job, and work_date combination
            $exists = $siteJob->casuals()
                ->where('casual_id', $casualId)
                ->wherePivot('work_date', $pivotData['work_date'])
                ->exists();

            if (!$exists) {
                $siteJob->casuals()->attach($casualId, $pivotData);
            }
        }

        $siteJob->load('casuals');

        return response()->json($siteJob->casuals, 201);
    }

    public function destroy(SiteJob $siteJob, Casual $casual): JsonResponse
    {
        $siteJob->casuals()->detach($casual->id);

        return response()->json(['message' => 'Casual unassigned from job successfully']);
    }

    public function destroyByWorkDate(Request $request, SiteJob $siteJob, Casual $casual): JsonResponse
    {
        $validated = $request->validate([
            'work_date' => 'nullable|date',
        ]);

        $siteJob->casuals()
            ->wherePivot('work_date', $validated['work_date'] ?? null)
            ->detach($casual->id);

        return response()->json(['message' => 'Casual unassigned from job for specified date']);
    }
}
