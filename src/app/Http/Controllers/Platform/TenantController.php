<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    public function index(Request $request)
    {
        $tenants = Tenant::query()
            ->when($request->search, fn($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->education_level, fn($q, $l) => $q->where('education_level', $l))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Platform/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status', 'education_level']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Platform/Tenants/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'education_level'   => 'required|in:SD,SMP,SMA',
            'email'             => 'required|email',
            'npsn'              => 'nullable|string|max:20|unique:central.tenants',
            'phone'             => 'nullable|string|max:20',
            'address'           => 'nullable|string',
            'subscription_plan' => 'required|in:starter,professional,enterprise',
            'admin_name'        => 'nullable|string|max:255',
            'admin_password'    => 'nullable|string|min:8',
        ]);

        $this->tenantService->createTenant($validated);

        return redirect()->route('platform.tenants.index')
            ->with('success', 'Sekolah berhasil ditambahkan.');
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('subscriptions');

        return Inertia::render('Platform/Tenants/Show', [
            'tenant' => $tenant,
        ]);
    }

    public function edit(Tenant $tenant)
    {
        return Inertia::render('Platform/Tenants/Edit', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'education_level'   => 'required|in:SD,SMP,SMA',
            'email'             => 'required|email',
            'phone'             => 'nullable|string|max:20',
            'address'           => 'nullable|string',
            'subscription_plan' => 'required|in:starter,professional,enterprise',
            'max_students'      => 'required|integer|min:1',
            'status'            => 'required|in:active,suspended,inactive',
        ]);

        $tenant->update($validated);

        return redirect()->route('platform.tenants.show', $tenant)
            ->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(Tenant $tenant)
    {
        $this->tenantService->deleteTenant($tenant);

        return redirect()->route('platform.tenants.index')
            ->with('success', 'Sekolah berhasil dihapus.');
    }
}
