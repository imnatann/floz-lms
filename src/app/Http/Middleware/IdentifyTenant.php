<?php

namespace App\Http\Middleware;

use App\Models\Central\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Identify the tenant from the request subdomain and configure the tenant database connection.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $slug = $this->extractSlug($host);

        // If no subdomain slug found, this is a platform/admin request — skip tenant setup
        if (!$slug) {
            return $next($request);
        }

        // Force URL generation to use the current request's scheme + host
        // so redirect(), route(), url() etc. preserve the tenant subdomain
        $rootUrl = $request->getSchemeAndHttpHost();
        URL::forceRootUrl($rootUrl);

        // Look up tenant in central database
        $tenant = Tenant::where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$tenant) {
            abort(404, 'Sekolah tidak ditemukan atau tidak aktif.');
        }

        // Configure the tenant database connection dynamically
        Config::set('database.connections.tenant.database', $tenant->database_name);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Switch Auth to use the Tenant User model
        Config::set('auth.providers.users.model', \App\Models\Tenant\User::class);
        Auth::getProvider()->setModel(\App\Models\Tenant\User::class);
        Auth::forgetGuards();

        // Bind tenant to the container for access throughout the request
        app()->instance('currentTenant', $tenant);

        // Share tenant data with Inertia
        $request->attributes->set('tenant', $tenant);

        // Store tenant info in session for downstream use
        session(['tenant_id' => $tenant->id, 'tenant_slug' => $tenant->slug, 'tenant_database' => $tenant->database_name]);

        return $next($request);
    }

    /**
     * Extract the subdomain slug from the host.
     */
    protected function extractSlug(string $host): ?string
    {
        // For local development: demo.localhost -> demo
        if (str_contains($host, 'localhost')) {
            $parts = explode('.', $host);
            if (count($parts) >= 2 && $parts[0] !== 'admin' && $parts[0] !== 'localhost') {
                return $parts[0];
            }
            return null;
        }

        // For production: school.floz.id -> school
        $parts = explode('.', $host);
        if (count($parts) >= 3 && $parts[0] !== 'admin' && $parts[0] !== 'api') {
            return $parts[0];
        }

        // Check for custom domain
        $tenant = Tenant::where('domain', $host)->where('status', 'active')->first();
        if ($tenant) {
            return $tenant->slug;
        }

        return null;
    }
}
