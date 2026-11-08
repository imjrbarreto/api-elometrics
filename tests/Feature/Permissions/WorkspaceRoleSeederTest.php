<?php

use Database\Seeders\WorkspacePermissionSeeder;
use Database\Seeders\WorkspaceRoleSeeder;
use Spatie\Permission\Models\Role;

it('assigns member management permissions to the owner role', function () {
    /** @var \Tests\TestCase $this */

    $this->seed([
        WorkspacePermissionSeeder::class,
        WorkspaceRoleSeeder::class,
    ]);

    $ownerRole = Role::query()
        ->where('name', 'owner')
        ->where('guard_name', 'api')
        ->whereNull('workspace_id')
        ->firstOrFail();

    $permissions = $ownerRole->permissions()
        ->pluck('name')
        ->all();

    expect($permissions)->toContain(
        'members.view',
        'members.add',
        'members.remove',
    );

    expect($permissions)
        ->not->toContain('training-sessions.respond');
});