<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

it('can delete a workspace', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $workspace = Workspace::factory()->create(['user_id' => $user->id]);

    Passport::actingAs($user);

    $this->deleteJson(apiUrl("workspaces/{$workspace->id}"))
        ->assertNoContent();

    $this->assertDatabaseMissing('workspaces', [
        'id' => $workspace->id,
    ]);
});