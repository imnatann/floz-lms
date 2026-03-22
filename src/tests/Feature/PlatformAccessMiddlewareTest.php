<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatformAccessMiddlewareTest extends TestCase
{
    private string $centralDatabase;

    private string $tenantDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centralDatabase = database_path('central_test.sqlite');
        $this->tenantDatabase = database_path('tenant_test.sqlite');

        @unlink($this->centralDatabase);
        @unlink($this->tenantDatabase);

        touch($this->centralDatabase);
        touch($this->tenantDatabase);

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.central', [
            'driver' => 'sqlite',
            'database' => $this->centralDatabase,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => $this->tenantDatabase,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('central');
        DB::purge('tenant');
        DB::reconnect('central');
        DB::reconnect('tenant');

        Schema::connection('central')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('database_name')->unique();
            $table->string('domain')->unique()->nullable();
            $table->string('education_level', 50);
            $table->string('email');
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('central');
        DB::disconnect('tenant');

        @unlink($this->centralDatabase);
        @unlink($this->tenantDatabase);

        parent::tearDown();
    }

    public function test_guest_is_redirected_from_platform_routes(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->get('/platform/tenants');

        $response->assertRedirect('/login');
    }

    public function test_super_admin_can_access_platform_routes_from_central_host(): void
    {
        $user = User::factory()->make([
            'role' => UserRole::SuperAdmin,
        ]);

        $response = $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->get('/platform/tenants');

        $response->assertOk();
    }

    public function test_central_non_super_admin_is_denied_from_platform_routes(): void
    {
        $user = User::factory()->make([
            'role' => UserRole::SchoolAdmin,
        ]);

        $response = $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->get('/platform/tenants');

        $response->assertForbidden();
    }

    public function test_tenant_user_is_denied_from_platform_routes(): void
    {
        Tenant::query()->create([
            'name' => 'Demo School',
            'slug' => 'demo',
            'database_name' => $this->tenantDatabase,
            'domain' => null,
            'education_level' => 'SMA',
            'email' => 'school@example.com',
            'status' => 'active',
        ]);

        $user = new TenantUser([
            'name' => 'Tenant Admin',
            'email' => 'tenant@example.com',
            'password' => 'password',
            'role' => UserRole::SchoolAdmin,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'demo.localhost'])
            ->get('/platform/tenants');

        $response->assertForbidden();
    }
}
