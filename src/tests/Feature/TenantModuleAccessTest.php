<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\IdentifyTenant;
use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantModuleAccessTest extends TestCase
{
    private string $centralDatabase;

    private string $tenantDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centralDatabase = database_path('central_module_test.sqlite');
        $this->tenantDatabase = database_path('tenant_module_test.sqlite');

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

        $this->tenant = Tenant::query()->create([
            'name' => 'Demo School',
            'slug' => 'demo',
            'database_name' => $this->tenantDatabase,
            'domain' => null,
            'education_level' => 'SMA',
            'email' => 'school@example.com',
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('central');
        DB::disconnect('tenant');

        @unlink($this->centralDatabase);
        @unlink($this->tenantDatabase);

        parent::tearDown();
    }

    public function test_teacher_cannot_access_schedule_admin_page(): void
    {
        $teacher = $this->makeTenantUser(UserRole::Teacher, 'teacher@example.com');

        $response = $this->tenantRequest($teacher, '/tenant/schedules');

        $response->assertForbidden();
    }

    public function test_teacher_cannot_access_announcement_management_page(): void
    {
        $teacher = $this->makeTenantUser(UserRole::Teacher, 'teacher2@example.com');

        $response = $this->tenantRequest($teacher, '/tenant/announcements/create');

        $response->assertForbidden();
    }

    public function test_student_cannot_access_report_card_management_page(): void
    {
        $student = $this->makeTenantUser(UserRole::Student, 'student@example.com');

        $response = $this->tenantRequest($student, '/tenant/report-cards');

        $response->assertForbidden();
    }

    public function test_school_admin_can_access_announcement_management_page(): void
    {
        $admin = $this->makeTenantUser(UserRole::SchoolAdmin, 'admin@example.com');

        $response = $this->tenantRequest($admin, '/tenant/announcements/create');

        $response->assertOk();
    }

    private function makeTenantUser(UserRole $role, string $email): TenantUser
    {
        return TenantUser::query()->create([
            'name' => ucfirst($role->value),
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function tenantRequest(TenantUser $user, string $uri)
    {
        app()->instance('currentTenant', $this->tenant);

        return $this->withoutMiddleware(IdentifyTenant::class)
            ->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'demo.localhost'])
            ->get($uri);
    }
}
