<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CostCentre;
use Illuminate\Http\Request;

class CostCentreController extends Controller
{
    public function index()
    {
        return CostCentre::with('department')
            ->orderBy('code')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:cost_centres',
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'budget' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $costCentre = CostCentre::create($validated);

        return response()->json($costCentre->load('department'), 201);
    }

    public function show(CostCentre $costCentre)
    {
        return $costCentre->load('department');
    }

    public function update(Request $request, CostCentre $costCentre)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:cost_centres,code,' . $costCentre->id,
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'budget' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $costCentre->update($validated);

        return response()->json($costCentre->load('department'));
    }

    public function destroy(CostCentre $costCentre)
    {
        $costCentre->delete();
        return response()->json(null, 204);
    }
}
