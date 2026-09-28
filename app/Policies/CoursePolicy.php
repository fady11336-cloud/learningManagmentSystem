<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CoursePolicy
{

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role->name === 'instructor';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->role->name === 'instructor' && $user->id === $course->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->role->name === 'instructor' && $user->id === $course->user_id;
    }

    public function publish(User $user, Course $course): bool
    {
        return $user->role->name === 'instructor' && $user->id === $course->user_id;
    }

    public function archieve(User $user, Course $course): bool
    {
        return $user->role->name === 'instructor' && $user->id === $course->user_id;
    }

    public function isStudent(User $user, Course $course)
    {
        return $user->role->name === 'student' && $course->visibility === 'published';
    }

}
