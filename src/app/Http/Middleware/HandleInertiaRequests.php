<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user()?->load(['student:id,email', 'teacher:id,email']),
                'permissions' => fn () => $request->user() ? [
                    'manage_students' => $request->user()->can('create', \App\Models\Tenant\Student::class),
                    'manage_teachers' => $request->user()->can('create', \App\Models\Tenant\Teacher::class),
                    'manage_grades' => $request->user()->can('create', \App\Models\Tenant\Grade::class),
                    'view_all_students' => $request->user()->can('viewAny', \App\Models\Tenant\Student::class),
                    'manage_classes' => $request->user()->isSchoolAdmin(),
                    'manage_subjects' => $request->user()->isSchoolAdmin(),
                    'manage_assignments' => $request->user()->isSchoolAdmin(),
                ] : [],
            ],
            'tenant' => fn () => $request->attributes->get('tenant') ?? (app()->bound('currentTenant') ? app('currentTenant') : null),
            'subscription' => fn () => $request->attributes->get('subscription'),
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error'   => fn () => $request->session()->get('error'),
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }
}
