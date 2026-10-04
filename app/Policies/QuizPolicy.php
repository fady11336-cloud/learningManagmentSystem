<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuizPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Course $course): bool
    {
        if($user->role->name === 'instructor')
            {
                return $user->id === $course->user_id;
            }
        if($user->role->name === 'student')
            {
                return $course->enrollments()->where('user_id', $user->id)->exists();
            }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Quiz $quiz): bool
    {
        if($user->role->name === 'instructor')
            {
                return $user->id === $quiz->course->user_id;
            }
        if($user->role->name === 'student')
            {
                return $quiz->course->enrollments()->where('user_id', $user->id)->exists();
            }

        return false;   
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Course $course): bool
    {
        if($user->role->name === 'instructor')
            {
                return $user->id === $course->user_id;
            }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Quiz $quiz): bool
    {
        return $user->role->name === 'instructor' && $user->id === $quiz->course->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->role->name === 'instructor' && $user->id === $quiz->course->user_id;
    }

}
