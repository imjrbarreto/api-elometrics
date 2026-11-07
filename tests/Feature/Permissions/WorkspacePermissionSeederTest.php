<?php

use Database\Seeders\WorkspacePermissionSeeder;

it('creates the workspace permissions for the api guard', function () {
    // Actuar: ejecutar el seeder.
    $this->seed(WorkspacePermissionSeeder::class);

    // Comprobar: los cuatro permisos existen.
    foreach ([
        'members.view',
        'members.add',
        'members.remove',
        'training-sessions.respond',
    ] as $permission) {
        $this->assertDatabaseHas('permissions', [
            'name' => $permission,
            'guard_name' => 'api',
        ]);
    }
});

it('does not duplicate permissions when executed again', function () {
    $this->seed(WorkspacePermissionSeeder::class);

    $existingPermissions = \Spatie\Permission\Models\Permission::query()
        ->where('guard_name', 'api')
        ->whereIn('name', [
            'members.view',
            'members.add',
            'members.remove',
            'training-sessions.respond',
        ])
        ->orderBy('name')
        ->pluck('id', 'name')
        ->all();

    $this->seed(WorkspacePermissionSeeder::class);

    $currentPermissions = \Spatie\Permission\Models\Permission::query()
        ->where('guard_name', 'api')
        ->whereIn('name', [
            'members.view',
            'members.add',
            'members.remove',
            'training-sessions.respond',
        ])
        ->orderBy('name')
        ->pluck('id', 'name')
        ->all();

    expect($currentPermissions)->toHaveCount(4);
    expect($currentPermissions)->toBe($existingPermissions);
});