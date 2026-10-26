<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('can create a workspace', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Passport::actingAs($user);

    $this->postJson(apiUrl('workspaces'), [
        'title' => 'Workspace 1',
        'status' => 'active',
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Workspace 1')
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('workspaces', [
        'title' => 'Workspace 1',
        'status' => 'active',
        'user_id' => $user->id,
    ]);
});

it('requires a title to create a workspace', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    Passport::actingAs($user);

    $this->postJson(apiUrl('workspaces'), [
        'status' => 'active',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);

    $this->assertDatabaseCount('workspaces', 0);
});