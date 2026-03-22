<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\IdentifyTenant;
use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthContextBoundaryTest extends TestCase
{
    private string $centralDatabase;

    private string $tenantDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centralDatabase = database_path('central_auth_test.sqlite');
        $this->tenantDatabase = database_path('tenant_auth_test.sqlite');

        @unlink($this->centralDatabase);
        @unlink($this->tenantDatabase);

        touch($this->centralDatabase);
        touch($this->tenantDatabase);

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
        Config::set('database.default', 'central');
        Config::set('tenancy.central_domain', 'admin.floz.test');

        DB::purge('central');
        DB::purge('tenant');
        DB::reconnect('central');
        DB::reconnect('tenant');

        Schema::connection('central')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('super_admin');
            $table->boolean('is_active')->default(true);
            $table->rememberToken()->nullable();
            $table->timestamps();
        });

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

        Schema::connection('tenant')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 20)->default('teacher');
            $table->boolean('is_active')->default(true);
            $table->rememberToken()->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event');
            $table->string('method')->nullable();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('url')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('students', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
        });

        Schema::connection('tenant')->create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
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

    public function test_central_super_admin_login_redirects_to_platform_dashboard(): void
    {
        User::query()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SuperAdmin,
        ]);

        $response = $this->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->post('/login', [
                'email' => 'admin@example.com',
                'password' => 'password',
            ]);

        $response->assertRedirect('/platform/dashboard');
    }

    public function test_identify_tenant_switches_context_for_tenant_host(): void
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

        $middleware = new IdentifyTenant();
        $request = Request::create('http://demo.localhost/login', 'GET');

        $response = $middleware->handle($request, fn () => response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertTrue(app()->bound('currentTenant'));
        $this->assertSame('demo', app('currentTenant')->slug);
        $this->assertSame(TenantUser::class, Config::get('auth.providers.users.model'));
    }

    public function test_central_super_admin_cannot_access_tenant_routes(): void
    {
        $user = User::factory()->make([
            'role' => UserRole::SuperAdmin,
        ]);

        $response = $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->get('/tenant/subscription/expired');

        $response->assertForbidden();
    }

    public function test_tenant_user_can_access_tenant_routes_with_tenant_context(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Demo School',
            'slug' => 'demo',
            'database_name' => $this->tenantDatabase,
            'domain' => null,
            'education_level' => 'SMA',
            'email' => 'school@example.com',
            'status' => 'active',
        ]);

        $user = TenantUser::query()->create([
            'name' => 'Teacher',
            'email' => 'teacher@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Teacher,
            'is_active' => true,
        ]);

        app()->instance('currentTenant', $tenant);

        $response = $this->withoutMiddleware(IdentifyTenant::class)
            ->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'demo.localhost'])
            ->get('/tenant/subscription/expired');

        $response->assertOk();
    }
}
