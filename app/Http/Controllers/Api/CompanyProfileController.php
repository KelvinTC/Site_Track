<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CompanyProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $company = $request->user()->currentCompany;

        if (!$company) {
            return response()->json(['message' => 'No company selected'], 404);
        }

        return response()->json([
            'id' => $company->id,
            'company_name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'website' => $company->website,
            'address' => $company->address,
            'city' => $company->city,
            'state' => $company->state,
            'zip' => $company->zip,
            'tax_id' => $company->tax_id,
            'vat_number' => $company->vat_number,
            'vat_rate' => $company->vat_rate,
            'vat_registered' => $company->vat_registered,
            'logo_path' => $company->logo_path,
            'logo_url' => $company->logo_path ? Storage::url($company->logo_path) : null,
            'invoice_prefix' => $company->invoice_prefix,
            'job_prefix' => $company->job_prefix,
            'employee_prefix' => $company->employee_prefix,
            'expense_prefix' => $company->expense_prefix,
            'invoice_notes' => $company->invoice_notes,
            'invoice_footer' => $company->invoice_footer,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $company = $request->user()->currentCompany;

        if (!$company) {
            return response()->json(['message' => 'No company selected'], 404);
        }

        $validated = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'vat_registered' => 'nullable|boolean',
            'invoice_prefix' => 'nullable|string|max:10',
            'job_prefix' => 'nullable|string|max:10',
            'employee_prefix' => 'nullable|string|max:10',
            'expense_prefix' => 'nullable|string|max:10',
            'invoice_notes' => 'nullable|string',
            'invoice_footer' => 'nullable|string',
        ]);

        // Map company_name to name
        if (isset($validated['company_name'])) {
            $validated['name'] = $validated['company_name'];
            unset($validated['company_name']);
        }

        $company->update($validated);

        return response()->json([
            'message' => 'Company profile updated',
            'company_name' => $company->name,
            'logo_url' => $company->logo_path ? Storage::url($company->logo_path) : null,
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $company = $request->user()->currentCompany;

        if (!$company) {
            return response()->json(['message' => 'No company selected'], 404);
        }

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Delete old logo if exists
        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        // Store new logo
        $path = $request->file('logo')->store('logos', 'public');

        $company->update(['logo_path' => $path]);

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'logo_path' => $path,
            'logo_url' => Storage::url($path),
        ]);
    }

    public function deleteLogo(Request $request): JsonResponse
    {
        $company = $request->user()->currentCompany;

        if (!$company) {
            return response()->json(['message' => 'No company selected'], 404);
        }

        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
            $company->update(['logo_path' => null]);
        }

        return response()->json([
            'message' => 'Logo removed successfully',
        ]);
    }
}
