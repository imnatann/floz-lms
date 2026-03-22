<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Events\AnnouncementPosted;
use App\Http\Middleware\IdentifyTenant;
use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantFeatureFlowsTest extends TestCase
{
    private string $centralDatabase;

    private string $tenantDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centralDatabase = database_path('central_feature_flow_test.sqlite');
        $this->tenantDatabase = database_path('tenant_feature_flow_test.sqlite');

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

        Schema::connection('tenant')->create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('grade_level')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('homeroom_teacher_id')->nullable();
            $table->integer('max_students')->default(40);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->decimal('kkm', 8, 2)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('academic_year_id')->nullable();
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
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('content');
            $table->text('excerpt')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('target_audience')->default('all');
            $table->boolean('is_pinned')->default(false);
            $table->string('type')->default('info');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('semesters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->integer('semester_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('semester_id');
            $table->unsignedBigInteger('teacher_id');
            $table->decimal('final_score', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->date('date');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('semester_id');
            $table->integer('rank')->nullable();
            $table->decimal('total_score', 10, 2)->default(0);
            $table->decimal('average_score', 10, 2)->default(0);
            $table->integer('attendance_present')->default(0);
            $table->integer('attendance_sick')->default(0);
            $table->integer('attendance_permit')->default(0);
            $table->integer('attendance_absent')->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('pdf_url')->nullable();
            $table->timestamps();
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

    public function test_report_card_generation_creates_ranked_records_for_each_student(): void
    {
        $admin = $this->makeTenantUser(UserRole::SchoolAdmin, 'admin@example.com');

        $teacherId = DB::connection('tenant')->table('teachers')->insertGetId([
            'name' => 'Teacher One',
            'email' => 'teacher@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classId = DB::connection('tenant')->table('classes')->insertGetId([
            'name' => 'X IPA 1',
            'homeroom_teacher_id' => $teacherId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectId = DB::connection('tenant')->table('subjects')->insertGetId([
            'code' => 'MAT',
            'name' => 'Matematika',
            'kkm' => 75,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $semesterId = DB::connection('tenant')->table('semesters')->insertGetId([
            'semester_number' => 1,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studentOneId = DB::connection('tenant')->table('students')->insertGetId([
            'nis' => '1001',
            'name' => 'Student One',
            'email' => 'student1@example.com',
            'class_id' => $classId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studentTwoId = DB::connection('tenant')->table('students')->insertGetId([
            'nis' => '1002',
            'name' => 'Student Two',
            'email' => 'student2@example.com',
            'class_id' => $classId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('grades')->insert([
            [
                'student_id' => $studentOneId,
                'subject_id' => $subjectId,
                'class_id' => $classId,
                'semester_id' => $semesterId,
                'teacher_id' => $teacherId,
                'final_score' => 95,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => $studentTwoId,
                'subject_id' => $subjectId,
                'class_id' => $classId,
                'semester_id' => $semesterId,
                'teacher_id' => $teacherId,
                'final_score' => 80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::connection('tenant')->table('attendance')->insert([
            [
                'student_id' => $studentOneId,
                'date' => now()->toDateString(),
                'status' => 'present',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => $studentTwoId,
                'date' => now()->toDateString(),
                'status' => 'sick',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->tenantPost($admin, '/tenant/report-cards/generate', [
            'class_id' => $classId,
            'semester_id' => $semesterId,
        ]);

        $response->assertRedirect("/tenant/report-cards?class_id={$classId}&semester_id={$semesterId}");

        $this->assertDatabaseHas('report_cards', [
            'student_id' => $studentOneId,
            'class_id' => $classId,
            'semester_id' => $semesterId,
            'rank' => 1,
            'status' => 'draft',
        ], 'tenant');

        $this->assertDatabaseHas('report_cards', [
            'student_id' => $studentTwoId,
            'class_id' => $classId,
            'semester_id' => $semesterId,
            'rank' => 2,
            'status' => 'draft',
        ], 'tenant');
    }

    public function test_published_announcement_creates_notifications_for_target_users(): void
    {
        Event::fake([AnnouncementPosted::class]);

        $admin = $this->makeTenantUser(UserRole::SchoolAdmin, 'publisher@example.com');
        $student = $this->makeTenantUser(UserRole::Student, 'student@example.com');
        $this->makeTenantUser(UserRole::Teacher, 'teacher@example.com');

        $response = $this->tenantPost($admin, '/tenant/announcements', [
            'title' => 'Ujian Tengah Semester',
            'content' => '<p>Silakan cek jadwal terbaru.</p>',
            'excerpt' => '',
            'target_audience' => 'students',
            'type' => 'info',
            'is_pinned' => true,
            'is_published' => true,
        ]);

        $response->assertRedirect('/tenant/announcements');

        $this->assertDatabaseHas('announcements', [
            'title' => 'Ujian Tengah Semester',
            'target_audience' => 'students',
            'is_published' => 1,
        ], 'tenant');

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => TenantUser::class,
            'notifiable_id' => $student->id,
        ], 'tenant');

        Event::assertDispatchedTimes(AnnouncementPosted::class, 1);
    }

    public function test_school_admin_can_create_and_delete_schedule_entries(): void
    {
        $admin = $this->makeTenantUser(UserRole::SchoolAdmin, 'scheduler@example.com');

        $teacherId = DB::connection('tenant')->table('teachers')->insertGetId([
            'name' => 'Teacher Two',
            'email' => 'teacher-two@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classId = DB::connection('tenant')->table('classes')->insertGetId([
            'name' => 'XI IPA 1',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectId = DB::connection('tenant')->table('subjects')->insertGetId([
            'code' => 'BIO',
            'name' => 'Biologi',
            'kkm' => 78,
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

        $createResponse = $this->tenantPost($admin, '/tenant/schedules', [
            'day_of_week' => 1,
            'items' => [[
                'teaching_assignment_id' => $assignmentId,
                'start_time' => '08:00',
                'end_time' => '09:00',
            ]],
        ]);

        $createResponse->assertSessionHas('success');
        $this->assertDatabaseHas('schedules', [
            'teaching_assignment_id' => $assignmentId,
            'day_of_week' => 1,
        ], 'tenant');

        $scheduleId = DB::connection('tenant')->table('schedules')->value('id');

        $deleteResponse = $this->tenantDelete($admin, "/tenant/schedules/{$scheduleId}");

        $deleteResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('schedules', [
            'id' => $scheduleId,
        ], 'tenant');
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

    private function tenantPost(TenantUser $user, string $uri, array $payload)
    {
        app()->instance('currentTenant', $this->tenant);

        return $this->withoutMiddleware(IdentifyTenant::class)
            ->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'demo.localhost'])
            ->post($uri, $payload);
    }

    private function tenantDelete(TenantUser $user, string $uri)
    {
        app()->instance('currentTenant', $this->tenant);

        return $this->withoutMiddleware(IdentifyTenant::class)
            ->actingAs($user)
            ->from('/tenant/schedules')
            ->withServerVariables(['HTTP_HOST' => 'demo.localhost'])
            ->delete($uri);
    }
}
