<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CompanyProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $profile = CompanyProfile::getProfile();

        return response()->json($profile);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:255',
            'logo_path' => 'nullable|string|max:255',
            'invoice_notes' => 'nullable|string',
            'invoice_footer' => 'nullable|string',
        ]);

        $profile = CompanyProfile::getProfile();
        $profile->update($validated);

        return response()->json($profile);
    }
}
