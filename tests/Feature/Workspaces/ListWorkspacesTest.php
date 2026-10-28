<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('can list workspaces', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $workspace = Workspace::factory()->create([
        'user_id' => $user->id,
    ]);

    Passport::actingAs($user);

    $this->getJson(apiUrl('workspaces'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $workspace->id);
});

it('does not list workspaces from other users', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // El workspace del usuario conectado
    $myWorkspace = Workspace::factory()->create(['user_id' => $user->id]);
    
    // El workspace de otra persona
    Workspace::factory()->create(['user_id' => $otherUser->id]);

    Passport::actingAs($user);

    $this->getJson(apiUrl('workspaces'))
        ->assertOk()
        ->assertJsonCount(1, 'data') // Solo debe listar el mío
        ->assertJsonPath('data.0.id', $myWorkspace->id);
});