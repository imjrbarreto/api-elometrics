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

it('registers a user with valid data', function () {
    /** @var \Tests\TestCase $this */
    
    // Preparar:
    $data = [
        'name' => 'Jaime Romero',
        'email' => 'jaime@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    //Actuar:
    $response = $this->postJson(apiUrl('register'), $data);

    //Comprobar
    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Jaime Romero')
        ->assertJsonPath('data.email', 'jaime@example.com')
        ->assertJsonMissingPath('data.password');

    $this->assertDatabaseHas('users', [
        'name' => 'Jaime Romero',
        'email' => 'jaime@example.com',
    ]);

    $user = User::where('email', 'jaime@example.com')->firstOrFail();

    expect(Hash::check('password123', $user->password))->toBeTrue();
});

it('rejects registration without a name', function () {
    /** @var \Tests\TestCase $this */

    // PREPARAR: datos sin el campo name
    $data = [
        'email' => 'jaime@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    // ACTUAR
    $response = $this->postJson(apiUrl('register'), $data);

    // COMPROBAR
    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('rejects registration when passwords do not match', function () {
    /** @var \Tests\TestCase $this */

    // PREPARAR
    $data = [
        'name' => 'Jaime Romero',
        'email' => 'jaime@example.com',
        'password' => 'password123',
        'password_confirmation' => 'otra-password',
    ];

    // ACTUAR
    $response = $this->postJson(apiUrl('register'), $data);

    // COMPROBAR
    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('rejects registration with an existing email', function () {
    /** @var \Tests\TestCase $this */

    // PREPARAR: crear un usuario y los datos del nuevo registro
    User::factory()->create([
        'email' => 'jaime@example.com',
    ]);

    $data = [
        'name' => 'Otro usuario',
        'email' => 'jaime@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    // ACTUAR
    $response = $this->postJson(apiUrl('register'), $data);

    // COMPROBAR
    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('returns an access token after registration', function () {
    /** @var \Tests\TestCase $this */

    // PREPARAR
    $data = [
        'name' => 'Jaime Romero',
        'email' => 'jaime@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    // ACTUAR
    $response = $this->postJson(apiUrl('register'), $data);

    // COMPROBAR
    $response
        ->assertCreated()
        ->assertJsonPath('token.token_type', 'Bearer')
        ->assertJsonStructure([
            'token' => ['access_token'],
        ]);
});
