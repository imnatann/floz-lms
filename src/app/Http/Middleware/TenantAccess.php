<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantAccess
{
    /**
     * Verify the authenticated user belongs to the current tenant.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app('currentTenant');

        if (!$tenant) {
            abort(403, 'Akses tidak diizinkan. Tenant tidak teridentifikasi.');
        }

        $user = $request->user();

        if (!$user) {
            return redirect()->route('tenant.login');
        }

        // Verify user exists in the tenant database
        // The user model uses tenant connection, so if we can retrieve it, they belong to this tenant
        if ($user->connection !== 'tenant') {
            abort(403, 'Akses tidak diizinkan untuk tenant ini.');
        }

        return $next($request);
    }
}
