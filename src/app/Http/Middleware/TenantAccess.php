<?php

namespace App\Http\Middleware;

use App\Models\Tenant\User as TenantUser;
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
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        if (!$tenant) {
            abort(403, 'Akses tidak diizinkan. Tenant tidak teridentifikasi.');
        }

        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (! $user instanceof TenantUser) {
            abort(403, 'Akses tidak diizinkan untuk tenant ini.');
        }

        return $next($request);
    }
}
