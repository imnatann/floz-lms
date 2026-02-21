<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileDashboardController;
use App\Http\Controllers\Api\MobileScheduleController;
use App\Http\Controllers\Api\MobileGradeController;
use App\Http\Controllers\Api\MobileAnnouncementController;
use App\Http\Controllers\Api\MobileReportCardController;
use App\Http\Controllers\Api\MobileAssignmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API Routes
|--------------------------------------------------------------------------
|
| JSON API endpoints for the FLOZ Mobile App (Flutter).
| All routes prefixed with /api/v1
| Tenant identified via X-Tenant-Slug header.
|
*/

Route::prefix('v1')->group(function () {

    // ─── Public (no auth required) ─────────────────────────────────
    Route::get('/tenants/search', [\App\Http\Controllers\Auth\LoginController::class, 'searchTenants']);

    // ─── Auth (tenant required, but no token yet) ──────────────────
    Route::middleware([\App\Http\Middleware\IdentifyTenantFromHeader::class])->group(function () {
        Route::post('/auth/login', [MobileAuthController::class, 'login']);
    });

    // ─── Protected (auth + tenant required) ────────────────────────
    Route::middleware([
        \App\Http\Middleware\IdentifyTenantFromHeader::class,
        'auth:sanctum',
    ])->group(function () {

        // Auth
        Route::post('/auth/logout', [MobileAuthController::class, 'logout']);
        Route::get('/auth/me', [MobileAuthController::class, 'me']);

        // Dashboard
        Route::get('/dashboard', [MobileDashboardController::class, 'index']);

        // Schedules
        Route::get('/schedules', [MobileScheduleController::class, 'index']);
        Route::get('/schedules/today', [MobileScheduleController::class, 'today']);

        // Grades
        Route::get('/grades', [MobileGradeController::class, 'index']);
        Route::get('/grades/{subjectId}', [MobileGradeController::class, 'show']);

        // Report Cards
        Route::get('/report-cards', [MobileReportCardController::class, 'index']);
        Route::get('/report-cards/{id}', [MobileReportCardController::class, 'show']);
        Route::get('/report-cards/{id}/pdf', [MobileReportCardController::class, 'pdf']);

        // Announcements
        Route::get('/announcements', [MobileAnnouncementController::class, 'index']);
        Route::get('/announcements/{id}', [MobileAnnouncementController::class, 'show']);

        // Assignments (Tugas)
        Route::get('/assignments', [MobileAssignmentController::class, 'index']);
        Route::get('/assignments/{id}', [MobileAssignmentController::class, 'show']);
    });
});
