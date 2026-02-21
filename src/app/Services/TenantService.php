<?php

namespace App\Services;

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantService
{
    /**
     * Create a new tenant with its own database.
     */
    public function createTenant(array $data): Tenant
    {
        $slug = Str::slug($data['name']);
        $databaseName = config('tenancy.database_prefix') . str_replace('-', '_', $slug);

        // Create tenant record in central database
        $tenant = Tenant::create([
            'name'              => $data['name'],
            'slug'              => $slug,
            'database_name'     => $databaseName,
            'domain'            => $data['domain'] ?? null,
            'education_level'   => $data['education_level'],
            'npsn'              => $data['npsn'] ?? null,
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'address'           => $data['address'] ?? null,
            'status'            => 'active',
            'subscription_plan' => $data['subscription_plan'] ?? 'starter',
            'max_students'      => $data['max_students'] ?? config('floz.plans.starter.max_students', 100),
        ]);

        // Create the tenant database
        $this->createDatabase($databaseName);

        // Run migrations on tenant database
        $this->runTenantMigrations($databaseName);

        // Create default admin user in tenant database
        $this->createDefaultAdmin($databaseName, $data);

        return $tenant;
    }

    /**
     * Create a new PostgreSQL database for the tenant.
     */
    protected function createDatabase(string $databaseName): void
    {
        DB::connection('central')->statement("CREATE DATABASE \"{$databaseName}\" OWNER postgres");
    }

    /**
     * Run tenant migrations on the specified database.
     */
    protected function runTenantMigrations(string $databaseName): void
    {
        // Temporarily configure tenant connection
        Config::set('database.connections.tenant.database', $databaseName);
        DB::purge('tenant');
        DB::reconnect('tenant');

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ]);
    }

    /**
     * Create a default school admin user in the tenant database.
     */
    protected function createDefaultAdmin(string $databaseName, array $data): void
    {
        Config::set('database.connections.tenant.database', $databaseName);
        DB::purge('tenant');
        DB::reconnect('tenant');

        DB::connection('tenant')->table('users')->insert([
            'name'       => $data['admin_name'] ?? 'Admin ' . $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['admin_password'] ?? 'password123'),
            'role'       => 'school_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Suspend a tenant — sets status to suspended.
     */
    public function suspendTenant(Tenant $tenant): void
    {
        $tenant->update(['status' => 'suspended']);
    }

    /**
     * Activate a tenant.
     */
    public function activateTenant(Tenant $tenant): void
    {
        $tenant->update(['status' => 'active']);
    }

    /**
     * Delete a tenant and its database.
     */
    public function deleteTenant(Tenant $tenant): void
    {
        // Drop the tenant database
        DB::connection('central')->statement("DROP DATABASE IF EXISTS \"{$tenant->database_name}\"");

        // Delete the tenant record
        $tenant->delete();
    }

    /**
     * Switch to a specific tenant's database context.
     */
    public function switchToTenant(Tenant $tenant): void
    {
        Config::set('database.connections.tenant.database', $tenant->database_name);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }
}
