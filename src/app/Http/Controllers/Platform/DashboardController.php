<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Models\Central\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_tenants'  => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'total_subscriptions' => Subscription::where('status', 'active')->count(),
            'revenue_monthly'     => Subscription::where('status', 'active')
                ->where('billing_cycle', 'monthly')
                ->sum('price'),
        ];

        $recentTenants = Tenant::latest()->take(5)->get();

        return Inertia::render('Platform/Dashboard', [
            'stats'         => $stats,
            'recentTenants' => $recentTenants,
        ]);
    }
}
