<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('can show a workspace', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $workspace = Workspace::factory()->create([
        'user_id' => $user->id,
        'title' => 'Workspace 1',
    ]);

    Passport::actingAs($user);

    $this->getJson(apiUrl("/workspaces/{$workspace->id}"))
        ->assertOk()
        ->assertJsonPath('data.id', $workspace->id)
        ->assertJsonPath('data.title', 'Workspace 1');
});