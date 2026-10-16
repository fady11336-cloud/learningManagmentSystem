<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Progress;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Models\Enrollment;
class ProgressPolicy
{
    /**
     * Determine whether the user can view any models.
     */

    public function me(User $user): bool 
    {
        return $user->role->name === 'student';
    }
    
    public function viewAny(User $user, Enrollment $enrollment): bool
    {
        if($user->role->name === 'student')
            {
                return $user->id === $enrollment->user_id;
            }
        
        if($user->role->name === 'instructor')
            {
                return $user->id === $enrollment->course->user_id;
            }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Course $course): bool
    {
        return $user->role->name === 'instructor' && $user->id === $course->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

}
