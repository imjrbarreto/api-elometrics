<?php

use App\Models\User;
use App\Models\Workspace;
use App\Support\WorkspaceRoles;
use Database\Seeders\WorkspaceRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');


expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});


function apiUrl(string $path): string
{
    return '/api/v1/' . ltrim($path, '/');
}

function emWorkspace(): array
{
    $owner = User::factory()->create();

    $workspace = Workspace::factory()->create([
        'user_id' => $owner->id,
    ]);

    return [$owner, $workspace];
}

function emMember(Workspace $workspace, string $role): User
{
    app(WorkspaceRoleSeeder::class)->run();

    $user = User::factory()->create();

    $workspace->members()->attach($user->id);

    WorkspaceRoles::within(
        $workspace,
        $user,
        fn (User $member) => $member->assignRole($role)
    );

    return $user;
}
