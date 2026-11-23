<?php

namespace App\Policies;

use App\Models\TrainingSession;
use App\Models\User;

class TrainingSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, TrainingSession $trainingSession): bool
    {
        return $user->id === $trainingSession->user_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TrainingSession $trainingSession): bool
    {
        return $user->id === $trainingSession->user_id;
    }

    public function delete(User $user, TrainingSession $trainingSession): bool
    {
        return $user->id === $trainingSession->user_id;
    }

    public function restore(User $user, TrainingSession $trainingSession): bool
    {
        return false;
    }

    public function forceDelete(User $user, TrainingSession $trainingSession): bool
    {
        return false;
    }
}
