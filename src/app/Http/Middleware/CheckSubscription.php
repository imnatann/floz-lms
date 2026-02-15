<?php

namespace App\Http\Middleware;

use App\Models\Central\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Verify the tenant has an active subscription.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app('currentTenant');

        if (!$tenant) {
            abort(403, 'Tenant tidak teridentifikasi.');
        }

        // Check if tenant has active subscription
        $activeSubscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->first();

        if (!$activeSubscription) {
            // Allow access to subscription/billing pages
            if ($request->routeIs('tenant.subscription.*')) {
                return $next($request);
            }

            return redirect()->route('tenant.subscription.expired');
        }

        // Share subscription info with request
        $request->attributes->set('subscription', $activeSubscription);

        return $next($request);
    }
}
