<?php

namespace App\Policies;

use App\Models\Attempt;
use App\Models\User;
use App\Models\Quiz;
use Illuminate\Auth\Access\Response;

class AttemptPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Quiz $quiz): bool
    {
        return $user->role->name === 'instructor' && $user->id === $quiz->course->user_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Attempt $attempt): bool
    {
        if ($user->role->name === 'instructor') 
        {
            return $user->id === $attempt->quiz->course->user_id;
        }

        if ($user->role->name === 'student') 
        {
            return $user->id === $attempt->user_id;
        }  

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Quiz $quiz): bool
    {
        return $user->role->name === 'student' && $quiz->course
        ->enrollments()->where('user_id',$user->id)->exists();
    }

}
