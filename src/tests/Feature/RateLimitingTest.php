<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    private string $centralDatabase;

    private string $tenantDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centralDatabase = database_path('central_rate_limit_test.sqlite');
        $this->tenantDatabase = database_path('tenant_rate_limit_test.sqlite');

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

        User::query()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SuperAdmin,
        ]);

        Tenant::query()->create([
            'name' => 'Demo School',
            'slug' => 'demo',
            'database_name' => $this->tenantDatabase,
            'domain' => null,
            'education_level' => 'SMA',
            'email' => 'school@example.com',
            'status' => 'active',
        ]);

        TenantUser::query()->create([
            'name' => 'School Admin',
            'email' => 'school-admin@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SchoolAdmin,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('admin@example.com|127.0.0.1');
        RateLimiter::clear('127.0.0.1');
        RateLimiter::clear('demo|127.0.0.1');

        DB::disconnect('central');
        DB::disconnect('tenant');

        @unlink($this->centralDatabase);
        @unlink($this->tenantDatabase);

        parent::tearDown();
    }

    public function test_web_login_is_rate_limited_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withServerVariables(['HTTP_HOST' => 'localhost'])
                ->post('/login', [
                    'email' => 'admin@example.com',
                    'password' => 'wrong-password',
                ]);

            $response->assertStatus(302);
        }

        $blockedResponse = $this->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->post('/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);

        $blockedResponse->assertStatus(429);
    }

    public function test_tenant_search_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $response = $this->withServerVariables(['HTTP_HOST' => 'localhost'])
                ->get('/api/tenants/search?q=ab');

            $response->assertOk();
        }

        $blockedResponse = $this->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->get('/api/tenants/search?q=ab');

        $blockedResponse->assertStatus(429);
    }

    public function test_mobile_login_is_rate_limited_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeaders(['X-Tenant-Slug' => 'demo'])
                ->postJson('/api/v1/auth/login', [
                    'email' => 'school-admin@example.com',
                    'password' => 'wrong-password',
                ]);

            $response->assertStatus(401);
        }

        $blockedResponse = $this->withHeaders(['X-Tenant-Slug' => 'demo'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'school-admin@example.com',
                'password' => 'wrong-password',
            ]);

        $blockedResponse->assertStatus(429);
    }
}
