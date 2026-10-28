<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('can update a workspace', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $workspace = Workspace::factory()->create([
        'user_id' => $user->id,
        'title' => 'Workspace 1',
        'status' => 'active',
    ]);

    Passport::actingAs($user);

    $this->patchJson(apiUrl("workspaces/{$workspace->id}"), [
        'title' => 'Nuevo titulo',
        'status' => 'paused',
    ])->assertOk()
        ->assertJsonPath('data.title', 'Nuevo titulo')
        ->assertJsonPath('data.status', 'paused');

    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'title' => 'Nuevo titulo',
        'status' => 'paused',
    ]);
});

it('rejects an empty title when updating a workspace', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $workspace = Workspace::factory()->create([
        'user_id' => $user->id,
        'title' => 'Grupo avanzado',
    ]);

    Passport::actingAs($user);

    $this->patchJson(apiUrl("workspaces/{$workspace->id}"), [
        'title' => '',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);

    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'title' => 'Grupo avanzado',
    ]);
});