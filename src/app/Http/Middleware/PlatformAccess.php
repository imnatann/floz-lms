<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformAccess
{
    /**
     * Ensure platform routes are only accessible from the central context.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('currentTenant')) {
            abort(403, 'Akses platform hanya tersedia di domain pusat.');
        }

        $user = $request->user();

        if (! $user instanceof User) {
            abort(403, 'Akses platform membutuhkan akun admin pusat.');
        }

        if (! $user->isSuperAdmin()) {
            abort(403, 'Akses platform hanya untuk super admin.');
        }

        if (! $this->isCentralHost($request->getHost())) {
            abort(403, 'Host platform tidak valid.');
        }

        return $next($request);
    }

    private function isCentralHost(string $host): bool
    {
        $centralDomain = config('tenancy.central_domain');

        $allowedHosts = array_filter([
            $centralDomain,
            'localhost',
            '127.0.0.1',
            'admin.localhost',
        ]);

        return in_array($host, $allowedHosts, true);
    }
}
