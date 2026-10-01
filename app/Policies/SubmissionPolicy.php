<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use App\Models\Course;
use Illuminate\Auth\Access\Response;

class SubmissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user,Course $course): bool
    {
        return $user->role->name === 'instructor' && $user->id === $course->user_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Course $course): bool
    {
        if($user->role->name === 'instructor')
            {
                return $user->id === $course->user_id;
            }
        if($user->role->name === 'student')
            {
                return $course->enrollments()->where('user_id',$user->id)->exists();
            }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Course $course): bool
    {
        return $user->role->name === 'student' && 
        $course->enrollments()->where('user_id',$user->id)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Submission $submission): bool
    {
        return $user->role->name === 'instructor' && $user->id === $submission->assignment->course->user_id;
    }

}
