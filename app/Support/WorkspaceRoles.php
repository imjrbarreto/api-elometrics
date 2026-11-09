<?php

namespace App\Support;

use App\Models\User;
use App\Models\Workspace;
use Closure;

class WorkspaceRoles
{
    public static function within(
        Workspace $workspace,
        User $user,
        Closure $callback
    ): mixed {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId($workspace->id);

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        try {
            return $callback($user);
        } finally {
            setPermissionsTeamId($previousTeamId);

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }
    }
}
