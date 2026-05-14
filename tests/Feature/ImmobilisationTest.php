<?php

use App\Models\CategorieImmobilisation;
use App\Models\Immobilisation;
use App\Models\Site;
use Database\Seeders\CodificationSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CodificationSeeder::class);
});

it('crée une immobilisation et génère le code automatiquement', function () {
    actingAsRole('gestionnaire_patrimoine');
    $cat = CategorieImmobilisation::factory()->create(['code' => 'MAT-INF']);
    $site = Site::factory()->create(['code' => 'SIEGE']);

    $response = postJson('/api/v1/immobilisations', [
        'libelle' => 'Ordinateur portable Dell',
        'categorie_id' => $cat->id,
        'site_id' => $site->id,
        'valeur_acquisition' => 850000,
    ])->assertCreated();

    expect($response->json('data.code_inventaire'))
        ->toContain('MAT-INF')
        ->toContain('SIEGE')
        ->toContain((string) now()->year);

    expect($response->json('data.qr_payload'))->toContain('sig=');
});

it('respecte un code fourni explicitement', function () {
    actingAsRole('gestionnaire_patrimoine');
    $cat = CategorieImmobilisation::factory()->create();
    $site = Site::factory()->create();

    postJson('/api/v1/immobilisations', [
        'code_inventaire' => 'CUSTOM-001',
        'libelle' => 'Bien custom',
        'categorie_id' => $cat->id,
        'site_id' => $site->id,
    ])->assertCreated()
        ->assertJsonPath('data.code_inventaire', 'CUSTOM-001');
});

it('refuse code inventaire dupliqué', function () {
    actingAsRole('gestionnaire_patrimoine');
    $cat = CategorieImmobilisation::factory()->create();
    $site = Site::factory()->create();
    Immobilisation::factory()->create(['code_inventaire' => 'DUP-X', 'categorie_id' => $cat->id, 'site_id' => $site->id]);

    postJson('/api/v1/immobilisations', [
        'code_inventaire' => 'DUP-X',
        'libelle' => 'Doublon',
        'categorie_id' => $cat->id,
        'site_id' => $site->id,
    ])->assertStatus(422)->assertJsonValidationErrors('code_inventaire');
});

it('liste les immobilisations avec filtres', function () {
    actingAsRole('gestionnaire_patrimoine');
    Immobilisation::factory()->count(3)->create(['statut' => 'actif']);
    Immobilisation::factory()->create(['statut' => 'reforme']);

    getJson('/api/v1/immobilisations?statut=actif')->assertOk();
    expect(getJson('/api/v1/immobilisations?statut=actif')->json('meta.total'))->toBe(3);
});

it('recherche par code via /by-code/{code}', function () {
    actingAsRole('inventoriste');
    $immo = Immobilisation::factory()->create(['code_inventaire' => 'TEST-CODE-001']);

    getJson('/api/v1/immobilisations/by-code/TEST-CODE-001')
        ->assertOk()
        ->assertJsonPath('data.id', $immo->id);
});

it('met à jour une immobilisation', function () {
    actingAsRole('gestionnaire_patrimoine');
    $immo = Immobilisation::factory()->create();

    putJson("/api/v1/immobilisations/{$immo->id}", ['libelle' => 'Nouveau libellé'])
        ->assertOk()
        ->assertJsonPath('data.libelle', 'Nouveau libellé');
});

it('refuse la suppression d\'une immo déjà réformée', function () {
    actingAsAdmin();
    $immo = Immobilisation::factory()->create(['statut' => 'reforme']);

    \Pest\Laravel\deleteJson("/api/v1/immobilisations/{$immo->id}")->assertStatus(422);
});
