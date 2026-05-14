<?php

use App\Models\CampagneInventaire;
use App\Models\Immobilisation;
use App\Models\Site;
use Database\Seeders\CodificationSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CodificationSeeder::class);
});

it('crée une campagne', function () {
    actingAsRole('gestionnaire_patrimoine');

    postJson('/api/v1/campagnes', [
        'code' => 'INV-TEST-001',
        'libelle' => 'Inventaire test',
        'type' => 'partiel',
        'date_debut_prevue' => '2026-06-01',
        'date_fin_prevue' => '2026-06-15',
        'perimetre' => ['sites' => [], 'categories' => []],
    ])->assertCreated()
        ->assertJsonPath('data.statut', 'preparation');
});

it('génère les fiches attendues pour le périmètre', function () {
    actingAsRole('gestionnaire_patrimoine');
    $site = Site::factory()->create();
    Immobilisation::factory()->count(5)->create(['site_id' => $site->id, 'statut' => 'actif']);
    Immobilisation::factory()->create(['site_id' => $site->id, 'statut' => 'reforme']);

    $campagne = CampagneInventaire::factory()->create([
        'perimetre' => ['sites' => [$site->id], 'categories' => []],
        'statut' => 'preparation',
    ]);

    postJson("/api/v1/campagnes/{$campagne->id}/generer-fiches")
        ->assertOk()
        ->assertJsonPath('data.fiches_generees', 5); // actif uniquement

    $this->assertDatabaseCount('fiches_inventaire', 5);
    expect($campagne->fresh()->statut)->toBe('en_cours');
});

it('calcule l\'avancement correctement', function () {
    actingAsRole('gestionnaire_patrimoine');
    $site = Site::factory()->create();
    Immobilisation::factory()->count(4)->create(['site_id' => $site->id]);

    $campagne = CampagneInventaire::factory()->create([
        'perimetre' => ['sites' => [$site->id]],
        'statut' => 'preparation',
    ]);
    postJson("/api/v1/campagnes/{$campagne->id}/generer-fiches");

    $resp = getJson("/api/v1/campagnes/{$campagne->id}/avancement")->assertOk();
    expect($resp->json('data.total'))->toBe(4)
        ->and($resp->json('data.taux_avancement_pct'))->toBe(0);
});

it('refuse génération sur campagne déjà en cours', function () {
    actingAsRole('gestionnaire_patrimoine');
    $campagne = CampagneInventaire::factory()->create(['statut' => 'en_cours']);

    postJson("/api/v1/campagnes/{$campagne->id}/generer-fiches")->assertStatus(422);
});

it('clôture une campagne en cours', function () {
    actingAsAdmin();
    $campagne = CampagneInventaire::factory()->create(['statut' => 'en_cours']);

    postJson("/api/v1/campagnes/{$campagne->id}/cloturer")
        ->assertOk()
        ->assertJsonPath('data.statut', 'cloturee');
});
