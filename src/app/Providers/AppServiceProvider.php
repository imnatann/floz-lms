<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Tenant\Student;
use App\Models\Tenant\Grade;
use App\Models\Tenant\Teacher;
use App\Policies\Tenant\StudentPolicy;
use App\Policies\Tenant\GradePolicy;
use App\Policies\Tenant\TeacherPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Grade::class, GradePolicy::class);
        Gate::policy(Teacher::class, TeacherPolicy::class);
        Gate::policy(\App\Models\Tenant\SchoolClass::class, \App\Policies\Tenant\SchoolClassPolicy::class);
        Gate::policy(\App\Models\Tenant\TeachingAssignment::class, \App\Policies\Tenant\TeachingAssignmentPolicy::class);

        if (\Illuminate\Support\Facades\Cache::get('query_logging_enabled')) {
            \Illuminate\Support\Facades\DB::listen(function ($query) {
                $location = collect(debug_backtrace())->filter(function ($trace) {
                    return isset($trace['file']) && !str_contains($trace['file'], 'vendor/');
                })->first();

                $log = sprintf(
                    "[%s] [%s] %s [%s] (File: %s:%s)",
                    now()->format('Y-m-d H:i:s'),
                    $query->time . 'ms',
                    $query->sql,
                    implode(', ', $query->bindings),
                    $location['file'] ?? 'unknown',
                    $location['line'] ?? 'unknown'
                );

                \Illuminate\Support\Facades\File::append(
                    storage_path('logs/query-' . now()->format('Y-m-d') . '.log'),
                    $log . PHP_EOL
                );
            });
        }
    }
}
