<?php

use App\Models\Central\Tenant;
use App\Models\Tenant\Announcement;
use App\Models\Tenant\Teacher;
use App\Models\Tenant\Attendance;
use App\Models\Tenant\Student;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

// 1. Setup Tenant Connection
$tenant = Tenant::first();
if (!$tenant) {
    echo "No tenant found.\n";
    exit(1);
}

echo "Testing with tenant: {$tenant->name} ({$tenant->database_name})\n";

Config::set('database.connections.tenant.database', $tenant->database_name);
DB::purge('tenant');
DB::reconnect('tenant');

// 2. Verify Announcements
echo "\n--- Testing Announcements ---\n";
try {
    $announcement = Announcement::create([
        'title' => 'Test Announcement ' . now(),
        'content' => 'This is a test content.',
        'type' => 'info',
        'is_published' => true,
        // 'user_id' => 1, // Assuming user 1 exists or is nullable/handled
    ]);
    echo "Created Announcement ID: {$announcement->id}\n";
    $count = Announcement::count();
    echo "Total Announcements: {$count}\n";
} catch (\Exception $e) {
    echo "Error creating announcement: " . $e->getMessage() . "\n";
}

// 3. Verify Teachers
echo "\n--- Testing Teachers ---\n";
try {
    $teacherCount = Teacher::count();
    echo "Total Teachers: {$teacherCount}\n";
    // Check if we can list them (basic query check)
    $teachers = Teacher::limit(1)->get();
    if ($teachers->isNotEmpty()) {
        echo "First Teacher: {$teachers->first()->name}\n";
    } else {
        echo "No teachers found. (Expected if db is empty)\n";
    }
} catch (\Exception $e) {
    echo "Error querying teachers: " . $e->getMessage() . "\n";
}

// 4. Verify Attendance
echo "\n--- Testing Attendance ---\n";
try {
    $student = Student::first();
    if ($student) {
        $attendance = Attendance::updateOrCreate(
            ['student_id' => $student->id, 'date' => now()->format('Y-m-d')],
            ['status' => 'present', 'notes' => 'Verified via script']
        );
        echo "Attendance recorded for Student ID {$student->id}: {$attendance->status}\n";
    } else {
        echo "No students found to test attendance.\n";
    }
} catch (\Exception $e) {
    echo "Error recording attendance: " . $e->getMessage() . "\n";
}

echo "\nVerification Complete.\n";
