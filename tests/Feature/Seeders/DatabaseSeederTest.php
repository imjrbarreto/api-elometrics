<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\WorkspacePermissionSeeder;
use Database\Seeders\WorkspaceRoleSeeder;

it('creates the initial roles permissions users and workspaces', function () {
    /** @var \Tests\TestCase $this */
    
    $this->seed(DatabaseSeeder::class);

    foreach (['owner', 'coach', 'student', 'viewer'] as $role) {
        $this->assertDatabaseHas('roles', [
            'name' => $role,
            'guard_name' => 'api',
            'workspace_id' => null,
        ]);
    }

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

    $this->assertDatabaseHas('users', [
        'name' => 'Pepe',
        'email' => 'pepe@mail.com',
    ]);

    $owner = User::where('email', 'pepe@mail.com')
        ->firstOrFail();

    foreach ([
        'Club de Ajedrez Central',
        'Entrenamiento Alto Rendimiento',
    ] as $title) {
        $this->assertDatabaseHas('workspaces', [
            'title' => $title,
            'status' => 'active',
            'user_id' => $owner->id,
        ]);
    }
});

it('does not duplicate roles or permissions when seeded again', function () {
    /** @var \Tests\TestCase $this */

    $this->seed([
        WorkspacePermissionSeeder::class,
        WorkspaceRoleSeeder::class,
    ]);

    $this->seed([
        WorkspacePermissionSeeder::class,
        WorkspaceRoleSeeder::class,
    ]);

    $this->assertDatabaseCount('roles', 4);
    $this->assertDatabaseCount('permissions', 4);
});

it('preserves the fixed user and workspaces when seeded again', function () {
    /** @var \Tests\TestCase $this */

    $this->seed(DatabaseSeeder::class);

    $originalOwnerId = User::where('email', 'pepe@mail.com')
        ->firstOrFail()
        ->id;

    $this->seed(DatabaseSeeder::class);

    expect(
        User::where('email', 'pepe@mail.com')->count()
    )->toBe(1);

    $this->assertDatabaseHas('users', [
        'id' => $originalOwnerId,
        'email' => 'pepe@mail.com',
    ]);
    
    $this->assertDatabaseCount('workspaces', 2);

    foreach ([
        'Club de Ajedrez Central',
        'Entrenamiento Alto Rendimiento',
    ] as $title) {
        $this->assertDatabaseHas('workspaces', [
            'title' => $title,
            'user_id' => $originalOwnerId,
        ]);
    }
});