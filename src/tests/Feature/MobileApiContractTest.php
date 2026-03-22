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

    private string $teacherToken;

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
            $table->decimal('kkm', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');
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

        Schema::connection('tenant')->create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_id');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->decimal('knowledge_score', 8, 2)->nullable();
            $table->decimal('final_score', 8, 2)->nullable();
            $table->string('predicate')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('semester_id');
            $table->decimal('average_score', 10, 2)->default(0);
            $table->decimal('total_score', 10, 2)->default(0);
            $table->integer('rank')->nullable();
            $table->integer('attendance_present')->default(0);
            $table->integer('attendance_sick')->default(0);
            $table->integer('attendance_permit')->default(0);
            $table->integer('attendance_absent')->default(0);
            $table->string('status')->default('draft');
            $table->string('pdf_url')->nullable();
            $table->timestamp('published_at')->nullable();
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

        Schema::connection('tenant')->create('offline_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('meeting_id')->nullable();
            $table->string('title');
            $table->text('description');
            $table->timestamp('due_date')->nullable();
            $table->string('status')->default('active');
            $table->string('type')->default('manual');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('offline_assignment_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offline_assignment_id');
            $table->unsignedBigInteger('class_id');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('offline_assignment_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offline_assignment_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
        });

        Schema::connection('tenant')->create('offline_assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offline_assignment_id');
            $table->unsignedBigInteger('student_id');
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('grade', 8, 2)->nullable();
            $table->text('correction_note')->nullable();
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
            'kkm' => 75,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $academicYearId = DB::connection('tenant')->table('academic_years')->insertGetId([
            'name' => '2025/2026',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $semesterId = DB::connection('tenant')->table('semesters')->insertGetId([
            'academic_year_id' => $academicYearId,
            'semester_number' => 1,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'is_active' => true,
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

        $teacherUser = TenantUser::query()->create([
            'name' => 'Teacher Mobile',
            'email' => 'teacher-api@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Teacher,
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

        DB::connection('tenant')->table('grades')->insert([
            'student_id' => 1,
            'subject_id' => $subjectId,
            'class_id' => $classId,
            'semester_id' => $semesterId,
            'teacher_id' => $teacherId,
            'knowledge_score' => 88,
            'final_score' => 90,
            'predicate' => 'A',
            'description' => 'Excellent progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('report_cards')->insert([
            'student_id' => 1,
            'class_id' => $classId,
            'semester_id' => $semesterId,
            'average_score' => 90,
            'total_score' => 90,
            'rank' => 1,
            'attendance_present' => 1,
            'status' => 'published',
            'pdf_url' => '/storage/report-cards/demo.pdf',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $offlineAssignmentId = DB::connection('tenant')->table('offline_assignments')->insertGetId([
            'teacher_id' => $teacherId,
            'subject_id' => $subjectId,
            'title' => 'Tugas Fisika 1',
            'description' => 'Kerjakan soal gerak lurus.',
            'due_date' => now()->addDays(2),
            'status' => 'active',
            'type' => 'manual',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('offline_assignment_classes')->insert([
            'offline_assignment_id' => $offlineAssignmentId,
            'class_id' => $classId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('tenant')->table('offline_assignment_files')->insert([
            'offline_assignment_id' => $offlineAssignmentId,
            'file_path' => 'assignments/sample.pdf',
            'file_name' => 'sample.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->token = $user->createToken('mobile')->plainTextToken;
        $this->teacherToken = $teacherUser->createToken('teacher-mobile')->plainTextToken;
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

    public function test_grade_endpoints_return_summary_and_detail_contracts(): void
    {
        $listResponse = $this->mobileGet('/api/v1/grades');

        $listResponse->assertOk()
            ->assertJsonStructure([
                'grades' => [[
                    'subject_id',
                    'subject_name',
                    'average',
                    'grade_count',
                ]],
            ]);

        $subjectId = $listResponse->json('grades.0.subject_id');

        $detailResponse = $this->mobileGet("/api/v1/grades/{$subjectId}");

        $detailResponse->assertOk()
            ->assertJsonStructure([
                'subject' => ['id', 'name'],
                'grades' => [[
                    'id',
                    'component',
                    'score',
                    'final_score',
                    'kkm',
                    'predicate',
                    'semester',
                    'created_at',
                ]],
            ]);
    }

    public function test_report_card_endpoints_return_list_detail_and_pdf_contracts(): void
    {
        $listResponse = $this->mobileGet('/api/v1/report-cards');

        $listResponse->assertOk()
            ->assertJsonStructure([
                'report_cards' => [[
                    'id',
                    'semester_name',
                    'academic_year',
                    'average_score',
                    'rank',
                    'published_at',
                ]],
            ]);

        $reportCardId = $listResponse->json('report_cards.0.id');

        $detailResponse = $this->mobileGet("/api/v1/report-cards/{$reportCardId}");
        $detailResponse->assertOk()
            ->assertJsonStructure([
                'id',
                'semester_name',
                'academic_year',
                'class_name',
                'average_score',
                'total_score',
                'rank',
                'attendance_present',
                'pdf_url',
            ]);

        $pdfResponse = $this->mobileGet("/api/v1/report-cards/{$reportCardId}/pdf");
        $pdfResponse->assertOk()->assertJsonStructure(['url']);
    }

    public function test_assignment_endpoints_return_list_and_detail_contracts(): void
    {
        $listResponse = $this->mobileGet('/api/v1/assignments');

        $listResponse->assertOk()
            ->assertJsonStructure([
                'current_page',
                'data' => [[
                    'id',
                    'title',
                    'description',
                    'due_date',
                    'subject',
                    'teacher',
                    'type',
                ]],
            ]);

        $assignmentId = $listResponse->json('data.0.id');

        $detailResponse = $this->mobileGet("/api/v1/assignments/{$assignmentId}");

        $detailResponse->assertOk()
            ->assertJsonStructure([
                'assignment' => [
                    'id',
                    'title',
                    'description',
                    'due_date',
                    'subject',
                    'teacher',
                    'type',
                    'files',
                    'submission',
                ],
            ]);
    }

    public function test_mobile_endpoints_require_authentication(): void
    {
        $response = $this->withHeaders([
            'X-Tenant-Slug' => 'demo',
            'Accept' => 'application/json',
        ])->getJson('/api/v1/dashboard');

        $response->assertUnauthorized();
    }

    public function test_teacher_is_forbidden_from_student_only_assignment_and_report_card_endpoints(): void
    {
        $assignmentResponse = $this->mobileGet('/api/v1/assignments', $this->teacherToken);
        $assignmentResponse->assertForbidden();

        $reportCardsResponse = $this->mobileGet('/api/v1/report-cards', $this->teacherToken);
        $reportCardsResponse->assertOk()->assertExactJson(['report_cards' => []]);
    }

    private function mobileGet(string $uri, ?string $token = null)
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.($token ?? $this->token),
            'X-Tenant-Slug' => 'demo',
            'Accept' => 'application/json',
        ])->getJson($uri);
    }
}
