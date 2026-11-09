<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;
use App\Support\WorkspaceRoles;

class WorkspacePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Workspace $workspace): bool
    {
        return $user->id === $workspace->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Workspace $workspace): bool
    {
        return $user->id === $workspace->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->id === $workspace->user_id;;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Workspace $workspace): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Workspace $workspace): bool
    {
        return false;
    }

    public function viewMembers(User $user, Workspace $workspace): bool 
    {
        if ($workspace->isOwnedBy($user)) {
            return true;
        }

        if (!$workspace->hasMember($user)) {
            return false;
        }

        return WorkspaceRoles::within(
            $workspace,
            $user,
            fn (User $member): bool => $member->hasPermissionTo(
                'members.view',
                'api'
            )
        );
    }

    public function addMember(User $user,Workspace $workspace): bool 
    {
        if ($workspace->isOwnedBy($user)) {
            return true;
        }

        if (! $workspace->hasMember($user)) {
            return false;
        }

        return WorkspaceRoles::within(
            $workspace,
            $user,
            fn (User $member): bool => $member->hasPermissionTo(
                'members.add',
                'api'
            )
        );
    }

    public function removeMember(User $user, Workspace $workspace, User $member): bool {
        if ($workspace->isOwnedBy($member)) {
            return false;
        }

        if (! $workspace->hasMember($user)) {
            return false;
        }

        if ($user->is($member)) {
            return true;
        }

        if ($workspace->isOwnedBy($user)) {
            return true;
        }

        return WorkspaceRoles::within(
            $workspace,
            $user,
            fn (User $actor): bool => $actor->hasPermissionTo(
                'members.remove',
                'api'
            )
        );
    }
}
