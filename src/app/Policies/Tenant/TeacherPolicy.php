<?php

namespace App\Policies\Tenant;

use App\Models\Tenant\Teacher;
use App\Models\Tenant\User;
use App\Enums\UserRole;

class TeacherPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSchoolAdmin() || $user->isTeacher();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Teacher $teacher): bool
    {
        return $user->isSchoolAdmin() || $user->isTeacher();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSchoolAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Teacher $teacher): bool
    {
        return $user->isSchoolAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->isSchoolAdmin();
    }
}
