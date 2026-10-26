<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);


it('rejects unauthenticated access to the profile', function () {
    /** @var \Tests\TestCase $this */

    // ACTUAR
    $response = $this->getJson(apiUrl('users/me'));

    // COMPROBAR
    $response->assertUnauthorized();
});

it('returns the authenticated user profile', function () {
    /** @var \Tests\TestCase $this */

    // PREPARAR
    $user = User::factory()->create();

    Passport::actingAs($user);

    // ACTUAR
    $response = $this->getJson(apiUrl('users/me'));

    // COMPROBAR
    $response
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonMissingPath('data.password');
});