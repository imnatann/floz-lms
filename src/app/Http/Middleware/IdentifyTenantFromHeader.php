<?php

namespace App\Http\Middleware;

use App\Models\Central\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identify tenant from X-Tenant-Slug header (for mobile API requests).
 * Unlike IdentifyTenant which uses subdomain detection, this middleware
 * reads the tenant slug from a request header sent by the mobile app.
 */
class IdentifyTenantFromHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->header('X-Tenant-Slug');

        if (!$slug) {
            return response()->json([
                'message' => 'X-Tenant-Slug header is required.',
            ], 422);
        }

        // Look up tenant in central database
        $tenant = Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'Sekolah tidak ditemukan atau tidak aktif.',
            ], 404);
        }

        // Configure the tenant database connection dynamically
        Config::set('database.connections.tenant.database', $tenant->database_name);
        
        // Purge current connections to ensure fresh config is used
        DB::purge('central');
        DB::purge('tenant');
        DB::purge(Config::get('database.default'));
        
        // Set tenant as the default connection for this request
        Config::set('database.default', 'tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');

        // Switch Auth to use the Tenant User model
        Config::set('auth.providers.users.model', \App\Models\Tenant\User::class);
        Auth::forgetGuards();

        // Bind tenant to the container for access throughout the request
        app()->instance('currentTenant', $tenant);

        return $next($request);
    }
}
