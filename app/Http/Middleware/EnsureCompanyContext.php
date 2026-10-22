<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->current_company_id) {
            return response()->json([
                'message' => 'No company context. Please select a company.',
                'error' => 'no_company_context'
            ], 403);
        }

        // Verify user still has access to the current company
        if (!$user->belongsToCompany($user->currentCompany)) {
            // Try to set a default company
            $defaultCompany = $user->companies()->wherePivot('is_default', true)->first();
            if (!$defaultCompany) {
                $defaultCompany = $user->companies()->first();
            }

            if ($defaultCompany) {
                $user->current_company_id = $defaultCompany->id;
                $user->save();
            } else {
                return response()->json([
                    'message' => 'You do not have access to any company.',
                    'error' => 'no_company_access'
                ], 403);
            }
        }

        return $next($request);
    }
}
