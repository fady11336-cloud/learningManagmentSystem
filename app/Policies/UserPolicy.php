<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{

    public function me(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

    public function updateProfile(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

    public function update(User $user, User $target): bool
    {
        return $user->id !== $target->id;
    }

    public function changeRole(User $user, User $target): bool
    {
        return $user->id !== $target->id;
    }

    public function block(User $user, User $target): bool
    {
        return $user->id !== $target->id && $target->role !== 'admin';
    }

    public function delete(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

}