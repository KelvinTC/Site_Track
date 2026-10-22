<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Owner bypasses all role checks
        if ($user->isOwner()) {
            return $next($request);
        }

        // Check if user has any of the required roles
        $userRole = $user->getRoleForCompany();

        if (!$userRole || !in_array($userRole, $roles)) {
            return response()->json([
                'message' => 'You do not have permission to perform this action.',
                'error' => 'insufficient_permissions',
                'required_roles' => $roles,
                'your_role' => $userRole,
            ], 403);
        }

        return $next($request);
    }
}
