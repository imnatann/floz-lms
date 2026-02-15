<?php

namespace App\Http\Middleware;

use App\Models\Central\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromSession
{
    /**
     * For local dev: restore the tenant database connection from session data
     * (set during login). In production, IdentifyTenant uses subdomains instead.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantDatabase = session('tenant_database');
        $tenantId       = session('tenant_id');

        if (!$tenantDatabase || !$tenantId) {
            // If no tenant in session, user might be a platform admin hitting tenant routes
            // Or session expired — redirect to login
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi tenant tidak ditemukan. Silakan login kembali.',
            ]);
        }

        // Fetch the tenant record to bind to the container
        $tenant = Tenant::find($tenantId);

        if (!$tenant || !$tenant->isActive()) {
            session()->forget(['tenant_id', 'tenant_slug', 'tenant_database']);
            return redirect()->route('login')->withErrors([
                'email' => 'Tenant tidak aktif. Hubungi admin platform.',
            ]);
        }

        // Configure the tenant database connection
        Config::set('database.connections.tenant.database', $tenantDatabase);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Also update the auth model to use tenant User
        Config::set('auth.providers.users.model', \App\Models\Tenant\User::class);
        Auth::getProvider()->setModel(\App\Models\Tenant\User::class);

        // Bind tenant to the container
        app()->instance('currentTenant', $tenant);

        return $next($request);
    }
}
