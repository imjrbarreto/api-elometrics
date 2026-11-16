<?php

use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\DemoSeeder;

it('creates a demo workspace with members sessions and a response', function () {
    /** @var \Tests\TestCase $this */

    $this->seed(DemoSeeder::class);

    $owner = User::where('email', 'owner@elometrics.test')
        ->firstOrFail();

    $workspace = Workspace::query()
        ->where('title', 'Demo: grupo avanzado')
        ->where('user_id', $owner->id)
        ->firstOrFail();

    expect($workspace->roleOf($owner))->toBe('owner');

    foreach (['coach', 'student', 'viewer'] as $role) {
        $member = User::where('email', "{$role}@elometrics.test")
            ->firstOrFail();

        expect($workspace->hasMember($member))->toBeTrue();
        expect($workspace->roleOf($member))->toBe($role);
    }

    expect($workspace->trainingSessions()->count())->toBe(2);

    $student = User::where('email', 'student@elometrics.test')
        ->firstOrFail();

    $this->assertDatabaseHas('training_session_responses', [
        'workspace_id' => $workspace->id,
        'user_id' => $student->id,
    ]);
});

it('does not duplicate demo data when seeded again', function () {
    /** @var \Tests\TestCase $this */

    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    $this->assertDatabaseCount('users', 4);
    $this->assertDatabaseCount('workspaces', 1);
    $this->assertDatabaseCount('training_sessions', 2);
    $this->assertDatabaseCount('training_session_responses', 1);

    $this->assertDatabaseCount('user_workspace', 3);

    $this->assertDatabaseCount('model_has_roles', 3);
});