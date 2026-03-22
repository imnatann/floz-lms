<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileApiContractTest extends TestCase
{
    private string $centralDatabase;

    private string $tenantDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centralDatabase = database_path('central_mobile_contract_test.sqlite');
        $this->tenantDatabase = database_path('tenant_mobile_contract_test.sqlite');

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
        Config::set('database.default', 'tenant');

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
            $table->string('role', 20)->default('student');
            $table->boolean('is_active')->default(true);
            $table->rememberToken()->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
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

        Schema::connection('tenant')->create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('homeroom_teacher_id')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_id');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('teaching_assignment_id');
            $table->integer('day_of_week');
            $table->string('start_time');
            $table->string('end_time');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('announcements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->date('date');
            $table->string('status');
            $table->timestamps();
        });

        Tenant::query()->create([
            'name' => 'Demo School',
            'slug' => 'demo',
            'database_name' => $this->tenantDatabase,
            'domain' => null,
            'education_level' => 'SMA',
            'email' => 'school@example.com',
            'status' => 'active',
        ]);

        $teacherId = DB::connection('tenant')->table('teachers')->insertGetId([
            'name' => 'Teacher API',
            'email' => 'teacher-api@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classId = DB::connection('tenant')->table('classes')->insertGetId([
            'name' => 'XII IPA 2',
            'homeroom_teacher_id' => $teacherId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectId = DB::connection('tenant')->table('subjects')->insertGetId([
            'name' => 'Fisika',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = TenantUser::query()->create([
            'name' => 'Student Mobile',
            'email' => 'student-mobile@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Student,
            'is_active' => true,
        ]);

        DB::connection('tenant')->table('students')->insert([
            'name' => 'Student Mobile',
            'email' => 'student-mobile@example.com',
            'class_id' => $classId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $assignmentId = DB::connection('tenant')->table('teaching_assignments')->insertGetId([
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
            'class_id' => $classId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('schedules')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'teaching_assignment_id' => $assignmentId,
            'day_of_week' => Carbon::today()->dayOfWeekIso,
            'start_time' => '07:00',
            'end_time' => '08:30',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('announcements')->insert([
            'user_id' => $user->id,
            'title' => 'Info Kelas',
            'content' => '<p>Kelas dimulai lebih awal besok.</p>',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('attendance')->insert([
            'student_id' => 1,
            'date' => now()->toDateString(),
            'status' => 'present',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->token = $user->createToken('mobile')->plainTextToken;
    }

    protected function tearDown(): void
    {
        DB::disconnect('central');
        DB::disconnect('tenant');

        @unlink($this->centralDatabase);
        @unlink($this->tenantDatabase);

        parent::tearDown();
    }

    public function test_dashboard_endpoint_returns_student_contract(): void
    {
        $response = $this->mobileGet('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('role', 'student')
            ->assertJsonStructure([
                'student' => ['id', 'name', 'class', 'homeroom_teacher'],
                'stats' => ['attendance_percentage'],
                'todays_schedules',
                'recent_announcements',
            ]);
    }

    public function test_schedule_endpoints_return_weekly_and_today_contracts(): void
    {
        $weeklyResponse = $this->mobileGet('/api/v1/schedules');

        $weeklyResponse->assertOk()
            ->assertJsonStructure([
                'schedules' => [[
                    'day',
                    'items' => [[
                        'id',
                        'start_time',
                        'end_time',
                        'subject',
                        'teacher',
                        'class',
                    ]],
                ]],
            ]);

        $todayResponse = $this->mobileGet('/api/v1/schedules/today');

        $todayResponse->assertOk()
            ->assertJsonStructure([
                'schedules' => [[
                    'id',
                    'start_time',
                    'end_time',
                    'subject',
                    'teacher',
                    'class',
                ]],
            ]);
    }

    public function test_announcement_endpoints_return_list_and_detail_contracts(): void
    {
        $listResponse = $this->mobileGet('/api/v1/announcements');

        $listResponse->assertOk()
            ->assertJsonStructure([
                'current_page',
                'data' => [[
                    'id',
                    'title',
                    'content',
                    'created_at',
                    'updated_at',
                ]],
            ]);

        $announcementId = $listResponse->json('data.0.id');

        $detailResponse = $this->mobileGet("/api/v1/announcements/{$announcementId}");

        $detailResponse->assertOk()
            ->assertJsonStructure([
                'announcement' => ['id', 'title', 'content', 'created_at', 'updated_at'],
            ]);
    }

    private function mobileGet(string $uri)
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'X-Tenant-Slug' => 'demo',
            'Accept' => 'application/json',
        ])->getJson($uri);
    }
}
