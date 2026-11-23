<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)
        ->createPersonalAccessGrantClient('Test Client');
});

it('rejects unauthenticated logout', function () {
    /** @var \Tests\TestCase $this */

    $response = $this->postJson(apiUrl('logout'));

    $response->assertUnauthorized();
});

it('revokes the current token on logout', function () {
    /** @var \Tests\TestCase $this */

    $user = User::factory()->create();

    $tokenResult = $user->createToken('Test Token');

    $response = $this
        ->withToken($tokenResult->accessToken)
        ->postJson(apiUrl('logout'));

    $response->assertNoContent();

    $this->assertDatabaseHas('oauth_access_tokens', [
        'id' => $tokenResult->token->id,
        'revoked' => true,
    ]);
});