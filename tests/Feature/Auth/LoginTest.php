<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)
        ->createPersonalAccessGrantClient('Test Client');
});

it('logs in with valid credentials', function () {
    /** @var \Tests\TestCase $this */

    // PREPARAR: un usuario registrado y sus credenciales
    User::factory()->create([
        'email' => 'jaime@example.com',
        'password' => Hash::make('password123'),
    ]);

    $data = [
        'email' => 'jaime@example.com',
        'password' => 'password123',
    ];

    // ACTUAR
    $response = $this->postJson(apiUrl('login'), $data);

    // COMPROBAR
    $response
        ->assertOk()
        ->assertJsonPath('data.email', 'jaime@example.com');
});

it('rejects login with an incorrect password', function () {
    /** @var \Tests\TestCase $this */

    // PREPARAR
    User::factory()->create([
        'email' => 'jaime@example.com',
        'password' => Hash::make('password123'),
    ]);

    $data = [
        'email' => 'jaime@example.com',
        'password' => 'incorrect-password',
    ];

    // ACTUAR
    $response = $this->postJson(apiUrl('login'), $data);

    // COMPROBAR
    $response->assertUnauthorized();
});

it('returns an access token after login', function () {
    /** @var \Tests\TestCase $this */

    // PREPARAR
    User::factory()->create([
        'email' => 'jaime@example.com',
        'password' => Hash::make('password123'),
    ]);

    $data = [
        'email' => 'jaime@example.com',
        'password' => 'password123',
    ];

    // ACTUAR
    $response = $this->postJson(apiUrl('login'), $data);

    // COMPROBAR
    $response
        ->assertOk()
        ->assertJsonPath('token.token_type', 'Bearer')
        ->assertJsonStructure([
            'token' => ['access_token'],
        ]);
});