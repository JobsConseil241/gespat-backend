<?php

use App\Models\Site;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('liste les sites pour un utilisateur autorisé', function () {
    actingAsAdmin();
    Site::factory()->count(3)->create();

    getJson('/api/v1/sites')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'code', 'libelle', 'type']], 'meta']);
});

it('refuse l\'accès aux sites pour un agent simple', function () {
    actingAsRole('agent_simple');

    getJson('/api/v1/sites')->assertStatus(403);
});

it('crée un site avec données valides', function () {
    actingAsAdmin();

    postJson('/api/v1/sites', [
        'code' => 'TST-001',
        'libelle' => 'Site de test',
        'type' => 'agence',
        'ville' => 'Libreville',
        'latitude' => 0.42,
        'longitude' => 9.45,
    ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'TST-001')
        ->assertJsonPath('data.libelle', 'Site de test');

    $this->assertDatabaseHas('sites', ['code' => 'TST-001']);
});

it('refuse code dupliqué', function () {
    actingAsAdmin();
    Site::factory()->create(['code' => 'DUP-1']);

    postJson('/api/v1/sites', [
        'code' => 'DUP-1',
        'libelle' => 'Doublon',
        'type' => 'agence',
    ])->assertStatus(422)->assertJsonValidationErrors('code');
});

it('refuse type invalide', function () {
    actingAsAdmin();

    postJson('/api/v1/sites', [
        'code' => 'INV-1',
        'libelle' => 'Test',
        'type' => 'invalide',
    ])->assertStatus(422)->assertJsonValidationErrors('type');
});

it('met à jour un site', function () {
    actingAsAdmin();
    $site = Site::factory()->create();

    putJson("/api/v1/sites/{$site->id}", ['libelle' => 'Nouveau libellé'])
        ->assertOk()
        ->assertJsonPath('data.libelle', 'Nouveau libellé');
});

it('supprime un site (soft delete)', function () {
    actingAsAdmin();
    $site = Site::factory()->create();

    deleteJson("/api/v1/sites/{$site->id}")->assertOk();
    $this->assertSoftDeleted('sites', ['id' => $site->id]);
});
