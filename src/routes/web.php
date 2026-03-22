<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\Platform\LogController;
use App\Http\Controllers\Tenant\DashboardController as TenantDashboardController;
use App\Http\Controllers\Tenant\StudentController;
use App\Http\Controllers\Tenant\GradeController;
use App\Http\Controllers\Tenant\ReportCardController;
use App\Http\Controllers\Tenant\TeacherController;
use App\Http\Controllers\Tenant\AnnouncementController;
use App\Http\Controllers\Tenant\AttendanceController;
use App\Http\Controllers\Tenant\SchoolClassController;
use App\Http\Controllers\Tenant\SubjectController;
use App\Http\Controllers\Tenant\TeachingAssignmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ─── Public / Auth ───────────────────────────────────────────────────
Route::get('/', [\App\Http\Controllers\WelcomeController::class, 'index'])->name('home');
Route::get('/docs', function () {
    return inertia('Docs');
})->name('docs');

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'showLoginForm')->name('login');
    Route::post('/login', 'login')->middleware('throttle:web-login');
    Route::post('/logout', 'logout')->name('logout');
    Route::get('/api/tenants/search', 'searchTenants')->middleware('throttle:tenant-search')->name('tenants.search');
});

// ─── Platform Routes (admin.floz.id) ────────────────────────────────
Route::prefix('platform')
    ->name('platform.')
    ->middleware(['auth', 'platform.access'])
    ->group(function () {
        Route::get('/dashboard', [PlatformDashboardController::class, 'index'])->name('dashboard');
        Route::resource('tenants', TenantController::class);

        // Logs
        Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
        Route::post('/logs/toggle-query', [LogController::class, 'toggleQueryLogging'])->name('logs.toggle-query');
        Route::post('/logs/system/clear', [LogController::class, 'clearSystemLogs'])->name('logs.clear-system');
        Route::post('/logs/queries/clear', [LogController::class, 'clearQueryLogs'])->name('logs.clear-queries');
    });

// ─── Tenant Routes (school.floz.id) ─────────────────────────────────
Route::prefix('tenant')
    ->name('tenant.')
    ->middleware(['auth', 'tenant.access'])
    ->group(function () {
        Route::get('/dashboard', [TenantDashboardController::class, 'index'])->name('dashboard');

        // Notifications
        // Notifications
        Route::get('/notifications/data', [\App\Http\Controllers\Tenant\NotificationController::class, 'data'])->name('notifications.data');
        Route::get('/notifications', [\App\Http\Controllers\Tenant\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Tenant\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\Tenant\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Tenant\NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::post('/notifications/{id}/mark-read', [\App\Http\Controllers\Tenant\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');

        // Students
        Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
        Route::get('students/template', [StudentController::class, 'downloadTemplate'])->name('students.template');
        Route::get('students/{student}/id-card', [StudentController::class, 'downloadIdCard'])->name('students.id-card');
        Route::resource('students', StudentController::class);

        // Grades
        Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
        Route::get('/grades/batch', [GradeController::class, 'batchInput'])->name('grades.batch');
        Route::post('/grades/batch', [GradeController::class, 'storeBatch'])->name('grades.storeBatch');

        // Report Cards
        Route::get('/report-cards', [ReportCardController::class, 'index'])->name('report-cards.index');
        Route::post('/report-cards/generate', [ReportCardController::class, 'generate'])->name('report-cards.generate');
        Route::get('/report-cards/{reportCard}', [ReportCardController::class, 'show'])->name('report-cards.show');
        Route::post('/report-cards/{reportCard}/publish', [ReportCardController::class, 'publish'])->name('report-cards.publish');
        Route::get('/report-cards/{reportCard}/pdf', [ReportCardController::class, 'downloadPdf'])->name('report-cards.pdf');

        // Staff (Teachers)
        Route::resource('staff', TeacherController::class)->parameters(['staff' => 'staff']);

        // Classes (Kelas)
        Route::resource('classes', SchoolClassController::class)->parameters(['classes' => 'class']);

        // Subjects (Mata Pelajaran)
        Route::resource('subjects', SubjectController::class);

        // Teaching Assignments (Penugasan Guru)
        Route::resource('teaching-assignments', TeachingAssignmentController::class)->except(['show']);

        // Schedules (Jadwal Pelajaran)
        Route::resource('schedules', \App\Http\Controllers\Tenant\ScheduleController::class)->only(['index', 'store', 'destroy']);

        // Attendance
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

        // Announcements
        Route::resource('announcements', AnnouncementController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

        // Audit Logs
        Route::get('/audit-logs', [\App\Http\Controllers\Tenant\AuditLogController::class, 'index'])->name('audit-logs.index');

        // Courses (Mata Pelajaran - Per Pertemuan)
        Route::get('courses', [\App\Http\Controllers\Tenant\MeetingController::class, 'index'])->name('courses.index');
        Route::get('courses/{teachingAssignment}', [\App\Http\Controllers\Tenant\MeetingController::class, 'show'])->name('courses.show');
        Route::put('meetings/{meeting}', [\App\Http\Controllers\Tenant\MeetingController::class, 'updateMeeting'])->name('meetings.update');
        Route::post('meetings/{meeting}/materials', [\App\Http\Controllers\Tenant\MeetingController::class, 'storeMaterial'])->name('meetings.materials.store');
        Route::delete('materials/{meetingMaterial}', [\App\Http\Controllers\Tenant\MeetingController::class, 'destroyMaterial'])->name('materials.destroy');

        // Assignments (Tugas)
        Route::post('assignments/{offlineAssignment}/submit', [\App\Http\Controllers\Tenant\OfflineAssignmentController::class, 'submit'])->name('assignments.submit');
        Route::get('assignments/{offlineAssignment}/student/{student}', [\App\Http\Controllers\Tenant\OfflineAssignmentController::class, 'showStudent'])->name('assignments.student.show');
        Route::post('assignments/{offlineAssignment}/student/{student}', [\App\Http\Controllers\Tenant\OfflineAssignmentController::class, 'storeCorrection'])->name('assignments.correction.store');
        Route::resource('assignments', \App\Http\Controllers\Tenant\OfflineAssignmentController::class)->parameters(['assignments' => 'offlineAssignment']);

        // Subscription (allow even when expired)
        Route::get('/subscription/expired', function () {
            return inertia('Tenant/Subscription/Expired');
        })->name('subscription.expired');
    });
