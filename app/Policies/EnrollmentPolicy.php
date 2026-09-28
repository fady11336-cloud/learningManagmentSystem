<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EnrollmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function me(User $user): bool
    {
        return $user->role->name === 'student';
    }

    public function viewAny(User $user, Course $course):bool
    {
    
        if($user->role->name === 'instructor')
            {
                return $user->id === $course->user_id;
            }
        return false; 
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->role->name === 'instructor' && $user->id === $enrollment->course->user_id;
    }

}
