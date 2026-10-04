<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuestionPolicy
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
    public function view(User $user, Question $question): bool
    {
        return $user->role->name === 'instructor' && $user->id === $question->quiz->course->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Quiz $quiz): bool
    {
        return $user->role->name === 'instructor' && $user->id === $quiz->course->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Question $question): bool
    {
        return $user->role->name === 'instructor' && $user->id === $question->quiz->course->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Question $question): bool
    {
        return $user->role->name === 'instructor' && $user->id === $question->quiz->course->user_id;
    }

}
