<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('liste les utilisateurs (super_admin)', function () {
    actingAsAdmin();
    User::factory()->count(3)->create();

    getJson('/api/v1/users')->assertOk()->assertJsonStructure(['data', 'meta']);
});

it('refuse accès aux utilisateurs pour un inventoriste', function () {
    actingAsRole('inventoriste');
    getJson('/api/v1/users')->assertStatus(403);
});

it('crée un utilisateur avec un rôle', function () {
    actingAsAdmin();

    $resp = postJson('/api/v1/users', [
        'matricule' => 'NEW-001',
        'nom' => 'Doe',
        'prenom' => 'John',
        'email' => 'john.doe@gespat.local',
        'password' => 'SecretP@ss1',
        'roles' => ['inventoriste'],
    ])->assertCreated();

    expect($resp->json('data.matricule'))->toBe('NEW-001');
    $this->assertDatabaseHas('users', ['email' => 'john.doe@gespat.local']);
});

it('change le mot de passe', function () {
    actingAsAdmin();
    $user = User::factory()->create();

    postJson("/api/v1/users/{$user->id}/password", [
        'password' => 'NewP@ss123',
    ])->assertOk();

    expect(\Illuminate\Support\Facades\Hash::check('NewP@ss123', $user->fresh()->password))->toBeTrue();
});

it('assigne des rôles', function () {
    actingAsAdmin();
    $user = User::factory()->create();

    postJson("/api/v1/users/{$user->id}/roles", [
        'roles' => ['comptable', 'auditeur_lecture'],
    ])->assertOk();

    expect($user->fresh()->getRoleNames()->toArray())
        ->toEqualCanonicalizing(['comptable', 'auditeur_lecture']);
});

it('empêche l\'auto-suppression', function () {
    $admin = actingAsAdmin();

    deleteJson("/api/v1/users/{$admin->id}")->assertStatus(422);
});

it('liste les rôles disponibles', function () {
    actingAsAdmin();

    $resp = getJson('/api/v1/roles')->assertOk();
    expect(collect($resp->json('data'))->pluck('name'))->toContain('super_admin', 'gestionnaire_patrimoine');
});
