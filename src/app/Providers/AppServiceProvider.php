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
    }
}
