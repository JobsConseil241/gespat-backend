<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('rejette login sans identifiants', function () {
    postJson('/api/v1/auth/login', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

it('rejette identifiants invalides', function () {
    User::factory()->create(['email' => 'foo@gespat.local']);

    postJson('/api/v1/auth/login', [
        'email' => 'foo@gespat.local',
        'password' => 'wrong',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('rejette utilisateur désactivé', function () {
    User::factory()->inactive()->create([
        'email' => 'inactive@gespat.local',
        'password' => bcrypt('secret'),
    ]);

    postJson('/api/v1/auth/login', [
        'email' => 'inactive@gespat.local',
        'password' => 'secret',
    ])
        ->assertStatus(422)
        ->assertJsonFragment(['email' => ['Ce compte est désactivé.']]);
});

it('connecte un utilisateur valide et retourne un token', function () {
    User::factory()->create([
        'email' => 'admin@gespat.local',
        'password' => bcrypt('secret'),
    ]);

    $response = postJson('/api/v1/auth/login', [
        'email' => 'admin@gespat.local',
        'password' => 'secret',
        'device_name' => 'tests',
    ])->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'matricule', 'email']]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

it('expose /auth/me avec un token valide', function () {
    actingAsAdmin();

    \Pest\Laravel\getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'matricule', 'email', 'roles']]);
});

it('refuse /auth/me sans token', function () {
    \Pest\Laravel\getJson('/api/v1/auth/me')->assertStatus(401);
});
