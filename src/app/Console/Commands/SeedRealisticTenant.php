<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use App\Models\Tenant\Teacher;
use App\Models\Tenant\Student;
use App\Models\Tenant\SchoolClass;
use App\Models\Tenant\AcademicYear;
use App\Models\Tenant\Semester;
use App\Models\Tenant\Subject;
use App\Models\Tenant\Schedule;
use App\Models\Tenant\Announcement;
use App\Models\Tenant\OfflineAssignment;
use App\Models\Tenant\OfflineAssignmentSubmission;
use App\Models\Tenant\Grade;
use App\Models\Tenant\ReportCard;
use App\Enums\UserRole;
use Carbon\Carbon;

class SeedRealisticTenant extends Command
{
    protected $signature = 'tenant:seed-realistic {slug}';
    protected $description = 'Fresh migrate, seed realistic data, and export to MD for a tenant.';

    public function handle()
    {
        $slug = $this->argument('slug');
        $tenant = Tenant::where('slug', $slug)->first();

        if (!$tenant) {
            $this->error("Tenant with slug {$slug} not found.");
            return 1;
        }

        $this->info("Found tenant: {$tenant->name}. Database: {$tenant->database_name}");

        config(['database.connections.tenant.database' => $tenant->database_name]);
        DB::purge('tenant');
        DB::setDefaultConnection('tenant');

        $this->info("Running fresh migration...");
        Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        $this->info("Seeding data...");
        $this->seedData();

        $this->info("Exporting to Markdown...");
        $this->exportToMarkdown($tenant->database_name);

        $this->info("Done!");
        return 0;
    }

    private function seedData()
    {
        $faker = \Faker\Factory::create('id_ID');
        $password = Hash::make('password123');

        // 1. Academic Year & Semester
        $academicYear = AcademicYear::forceCreate([
            'name' => '2025/2026',
            'is_active' => true,
            'start_date' => '2025-07-15',
            'end_date' => '2026-06-20',
        ]);

        $semester = Semester::forceCreate([
            'academic_year_id' => $academicYear->id,
            'semester_number' => 1,
            'start_date' => '2025-07-15',
            'end_date' => '2025-12-20',
            'is_active' => true,
        ]);

        // 2. Admin & Principal (Kepala Sekolah)
        User::forceCreate(['name' => 'Admin Sekolah', 'email' => 'admin@smp01.com', 'password' => $password, 'role' => UserRole::SchoolAdmin, 'is_active' => true, 'avatar_url' => 'https://ui-avatars.com/api/?name=Admin+Sekolah&background=random']);
        
        $kepsekUser = User::forceCreate(['name' => 'Drs. H. Sugeng Riyadi, M.Pd', 'email' => 'kepsek@smp01.com', 'password' => $password, 'role' => UserRole::Teacher, 'is_active' => true, 'avatar_url' => 'https://ui-avatars.com/api/?name=Sugeng+Riyadi&background=random']);
        Teacher::forceCreate([
            'user_id' => $kepsekUser->id, 
            'name' => $kepsekUser->name, 
            'email' => $kepsekUser->email, 
            'nip' => '196001011985031001', 
            'gender' => 'L',
            'birth_place' => 'Jakarta',
            'birth_date' => '1960-01-01',
            'phone' => $faker->phoneNumber,
            'address' => $faker->address,
            'photo_url' => $kepsekUser->avatar_url,
            'status' => 'active', 
            'is_homeroom' => false
        ]);

        // 3. Teachers & Classes
        $classes = ['VII A', 'VIII A', 'IX A'];
        $createdClasses = [];
        $teachers = [];

        foreach ($classes as $idx => $className) {
            $tUser = User::forceCreate([
                'name' => 'Guru ' . $className . ' ' . $faker->name,
                'email' => 'guru' . ($idx+1) . '@smp01.com',
                'password' => $password,
                'role' => UserRole::Teacher,
                'is_active' => true,
                'avatar_url' => 'https://ui-avatars.com/api/?name=Guru+'.urlencode($className).'&background=random'
            ]);
            $teacher = Teacher::forceCreate([
                'user_id' => $tUser->id,
                'name' => $tUser->name,
                'email' => $tUser->email,
                'nip' => '1980010120100' . $idx . '2001',
                'gender' => $faker->randomElement(['L', 'P']),
                'birth_place' => $faker->city,
                'birth_date' => $faker->date('Y-m-d', '1990-01-01'),
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'photo_url' => $tUser->avatar_url,
                'status' => 'active',
                'is_homeroom' => true
            ]);
            $teachers[] = $teacher;

            $class = SchoolClass::forceCreate([
                'name' => $className,
                'grade_level' => 7 + $idx,
                'academic_year_id' => $academicYear->id,
                'homeroom_teacher_id' => $teacher->id,
                'status' => 'active'
            ]);
            $createdClasses[] = $class;
        }

        // Additional Subject Teachers
        for ($i=0; $i<3; $i++) {
            $tName = $faker->name;
            $tUser = User::forceCreate([
                'name' => $tName, 
                'email' => 'guru_mapel' . $i . '@smp01.com', 
                'password' => $password, 
                'role' => UserRole::Teacher, 
                'is_active' => true,
                'avatar_url' => 'https://ui-avatars.com/api/?name='.urlencode($tName).'&background=random'
            ]);
            $teachers[] = Teacher::forceCreate([
                'user_id' => $tUser->id, 
                'name' => $tUser->name, 
                'email' => $tUser->email, 
                'nip' => '1990010120150' . $i . '2002', 
                'gender' => $faker->randomElement(['L', 'P']),
                'birth_place' => $faker->city,
                'birth_date' => $faker->date('Y-m-d', '1995-01-01'),
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'photo_url' => $tUser->avatar_url,
                'status' => 'active'
            ]);
        }

        // 4. Subjects
        $subjectNames = [
            'MAT' => 'Matematika', 
            'BND' => 'Bahasa Indonesia', 
            'IPA' => 'Ilmu Pengetahuan Alam', 
            'IPS' => 'Ilmu Pengetahuan Sosial', 
            'BIG' => 'Bahasa Inggris', 
            'PAI' => 'Pendidikan Agama Islam'
        ];
        $subjects = [];
        foreach ($subjectNames as $code => $sn) {
            $subjects[] = Subject::forceCreate(['code' => $code, 'name' => $sn, 'education_level' => 'SMP', 'grade_level' => '7', 'kkm' => 75, 'category' => $faker->randomElement(['A', 'B', 'C']), 'description' => 'Mata Pelajaran ' . $sn, 'status' => 'active']);
        }

        // 5. Students
        $students = [];
        foreach ($createdClasses as $cls) {
            for ($i=1; $i<=5; $i++) { 
                $sName = $faker->name;
                $sEmail = strtolower(str_replace([' ', '.'], '', $sName)) . '@siswa.smp01.com';
                $sUser = User::forceCreate(['name' => $sName, 'email' => $sEmail, 'password' => $password, 'role' => UserRole::Student, 'is_active' => true, 'avatar_url' => 'https://ui-avatars.com/api/?name='.urlencode($sName).'&background=random']);
                $students[] = Student::forceCreate([
                    'class_id' => $cls->id,
                    'name' => $sUser->name,
                    'email' => $sUser->email,
                    'nis' => '2025' . $cls->grade_level . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'nisn' => $faker->numerify('00########'),
                    'gender' => $faker->randomElement(['L', 'P']),
                    'birth_place' => $faker->city,
                    'birth_date' => $faker->date('Y-m-d', '2010-01-01'),
                    'religion' => $faker->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha']),
                    'address' => $faker->address,
                    'parent_name' => $faker->name,
                    'parent_phone' => $faker->phoneNumber,
                    'photo_url' => $sUser->avatar_url,
                    'family_card_number' => $faker->numerify('320###########'),
                    'nik' => $faker->numerify('320###########'),
                    'status' => 'active'
                ]);
            }
        }

        // 6. Schedules & Teaching Assignments
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach ($createdClasses as $cls) {
            foreach ($days as $dayIdx => $day) {
                // 2 subjects per day per class
                for ($j=0; $j<2; $j++) {
                    $sub = $faker->randomElement($subjects);
                    $tea = $faker->randomElement($teachers);
                    
                    $conditions = [
                        'academic_year_id' => $academicYear->id,
                        'class_id' => $cls->id,
                        'subject_id' => $sub->id,
                        'teacher_id' => $tea->id,
                    ];
                    $ta = DB::table('teaching_assignments')->where($conditions)->first();
                    $taId = $ta ? $ta->id : DB::table('teaching_assignments')->insertGetId(array_merge($conditions, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));

                    DB::table('schedules')->insert([
                        'id' => (string) Str::uuid(),
                        'teaching_assignment_id' => $taId,
                        'day_of_week' => $dayIdx + 1,
                        'start_time' => sprintf('%02d:00:00', 7 + ($j*2)),
                        'end_time' => sprintf('%02d:30:00', 8 + ($j*2)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 7. Announcements
        for ($i=1; $i<=3; $i++) {
            Announcement::forceCreate([
                'title' => 'Pengumuman ' . $i . ': ' . $faker->sentence,
                'content' => $faker->paragraph,
                'type' => $faker->randomElement(['info', 'warning', 'event']),
            ]);
        }

        // 8. Offline Assignments & Report Cards
        foreach ($createdClasses as $cls) {
            $sub = $subjects[0]; // Matematika
            $tea = $teachers[0];

            $assignment = OfflineAssignment::forceCreate([
                'title' => 'Tugas ' . $sub->name . ' - Bab 1',
                'description' => $faker->paragraph,
                'subject_id' => $sub->id,
                'teacher_id' => $tea->id,
                'due_date' => Carbon::now()->addDays(7),
                'status' => 'active',
                'created_by' => User::where('role', UserRole::Teacher->value)->first()->id,
            ]);

            DB::table('offline_assignment_classes')->insert([
                'offline_assignment_id' => $assignment->id,
                'class_id' => $cls->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Give grades to students in this class
            $classStudents = collect($students)->where('class_id', $cls->id);
            foreach ($classStudents as $stu) {
                
                // Submit Assignment
                OfflineAssignmentSubmission::forceCreate([
                    'offline_assignment_id' => $assignment->id,
                    'student_id' => $stu->id,
                    'submitted_at' => Carbon::now()->subDays(1),
                    'grade' => $faker->randomFloat(2, 70, 100),
                    'correction_note' => $faker->sentence,
                    'answer_text' => 'Ini jawaban saya bu guru.',
                ]);

                // Create Subject Grade
                Grade::forceCreate([
                    'student_id' => $stu->id,
                    'subject_id' => $sub->id,
                    'class_id' => $cls->id,
                    'semester_id' => $semester->id,
                    'teacher_id' => $tea->id,
                    'knowledge_score' => $faker->randomFloat(2, 75, 95),
                    'skill_score' => $faker->randomFloat(2, 75, 95),
                    'final_score' => $faker->randomFloat(2, 75, 95),
                    'predicate' => $faker->randomElement(['A', 'B', 'B+']),
                ]);

                // Report Cards
                ReportCard::forceCreate([
                    'student_id' => $stu->id,
                    'class_id' => $cls->id,
                    'semester_id' => $semester->id,
                    'total_score' => $faker->randomFloat(2, 400, 500),
                    'average_score' => $faker->randomFloat(2, 75, 95),
                    'rank' => $faker->numberBetween(1, 5),
                    'attendance_present' => 100,
                    'attendance_sick' => $faker->numberBetween(0, 2),
                    'attendance_permit' => $faker->numberBetween(0, 1),
                    'attendance_absent' => 0,
                    'achievements' => $faker->randomElement(['Juara 1 Lomba Pidato', 'Peserta Olimpiade Matematika', null]),
                    'notes' => 'Tetap semangat belajar.',
                    'behavior_notes' => 'Berkelakuan sangat baik di kelas.',
                    'homeroom_comment' => 'Tingkatkan prestasimu nak.',
                    'principal_comment' => 'Selamat atas prestasi yang diraih.',
                    'status' => 'published',
                    'published_at' => Carbon::now()
                ]);
            }
        }
    }

    private function exportToMarkdown($dbName)
    {
        $md = "# Tenant Database Content: `{$dbName}`\n\n";
        $md .= "> *Generated on " . Carbon::now()->toDateTimeString() . "*\n\n";
        
        $tables = [
            'Users' => User::class,
            'Students' => Student::class,
            'Teachers' => Teacher::class,
            'Classes' => SchoolClass::class,
            'Academic_Years' => AcademicYear::class,
            'Semesters' => Semester::class,
            'Subjects' => Subject::class,
            'Schedules' => Schedule::class,
            'Announcements' => Announcement::class,
            'Offline_Assignments' => OfflineAssignment::class,
            'Offline_Assignment_Submissions' => OfflineAssignmentSubmission::class,
            'Grades' => Grade::class,
            'Report_Cards' => ReportCard::class,
        ];

        foreach ($tables as $tableName => $modelClass) {
            $md .= "## Table: `{$tableName}`\n";
            $records = $modelClass::all();
            
            if ($records->isEmpty()) {
                $md .= "*No records found.*\n\n";
                continue;
            }

            $firstRecord = $records->first()->toArray();
            $keys = array_keys($firstRecord);

            // Hide password hashes and tokens for brevity
            $keys = array_filter($keys, fn($k) => !in_array($k, ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at', 'email_verified_at']));

            // Header
            $md .= "| " . implode(" | ", $keys) . " |\n";
            $md .= "|" . str_repeat("---|", count($keys)) . "\n";

            foreach ($records as $record) {
                $arr = tap($record->toArray(), function(&$a) use ($record) {
                    if (isset($a['role'])) {
                        $a['role'] = $record->role->value ?? $record->role;
                    }
                });
                
                $row = [];
                foreach ($keys as $k) {
                    $val = $arr[$k] ?? '';
                    if (is_array($val)) $val = json_encode($val);
                    if ($val instanceof \UnitEnum) $val = $val->value ?? $val->name;
                    if (is_string($val) && strlen($val) > 50) $val = substr($val, 0, 47) . '...';
                    $row[] = str_replace("|", "\\|", (string)$val);
                }
                $md .= "| " . implode(" | ", $row) . " |\n";
            }
            $md .= "\n";
        }

        $path = base_path('../floz_tenant_dummy_data.md');
        file_put_contents($path, $md);
        $this->info("Exported to: {$path}");
    }
}
