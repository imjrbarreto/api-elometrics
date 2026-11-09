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

it('assigns viewing and responding permissions to the student role', function () {
    /** @var \Tests\TestCase $this */

    $this->seed(WorkspaceRoleSeeder::class);

    $studentRole = Role::query()
        ->where('name', 'student')
        ->where('guard_name', 'api')
        ->whereNull('workspace_id')
        ->firstOrFail();

    $permissions = $studentRole->permissions()
        ->pluck('name')
        ->all();

    expect($permissions)
        ->toHaveCount(2)
        ->toContain(
            'members.view',
            'training-sessions.respond',
        );
});

it('assigns only member viewing permission to the viewer role', function () {
    /** @var \Tests\TestCase $this */

    $this->seed(WorkspaceRoleSeeder::class);

    $viewerRole = Role::query()
        ->where('name', 'viewer')
        ->where('guard_name', 'api')
        ->whereNull('workspace_id')
        ->firstOrFail();

    $permissions = $viewerRole->permissions()
        ->pluck('name')
        ->all();

    expect($permissions)
        ->toHaveCount(1)
        ->toContain('members.view');
});