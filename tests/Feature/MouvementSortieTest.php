<?php

use App\Models\Immobilisation;
use App\Models\Mouvement;
use App\Models\Site;
use App\Models\Sortie;
use Database\Seeders\CodificationSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CodificationSeeder::class);
});

it('crée un mouvement de transfert', function () {
    actingAsRole('gestionnaire_patrimoine');
    $site1 = Site::factory()->create();
    $site2 = Site::factory()->create();
    $immo = Immobilisation::factory()->create(['site_id' => $site1->id]);

    postJson('/api/v1/mouvements', [
        'immobilisation_id' => $immo->id,
        'type_mouvement' => 'transfert',
        'date_mouvement' => '2026-05-15',
        'site_destination_id' => $site2->id,
        'motif' => 'Réaffectation test',
    ])
        ->assertCreated()
        ->assertJsonPath('data.statut', 'propose')
        ->assertJsonPath('data.site_origine_id', $site1->id);
});

it('validation d\'un mouvement applique le changement sur l\'immobilisation', function () {
    actingAsAdmin();
    $site1 = Site::factory()->create();
    $site2 = Site::factory()->create();
    $immo = Immobilisation::factory()->create(['site_id' => $site1->id]);
    $mvt = Mouvement::create([
        'immobilisation_id' => $immo->id,
        'type_mouvement' => 'transfert',
        'date_mouvement' => now(),
        'site_origine_id' => $site1->id,
        'site_destination_id' => $site2->id,
        'statut' => 'propose',
    ]);

    postJson("/api/v1/mouvements/{$mvt->id}/valider")->assertOk();

    expect($immo->fresh()->site_id)->toBe($site2->id)
        ->and($mvt->fresh()->statut)->toBe('execute');
});

it('refuse la double validation d\'un mouvement', function () {
    actingAsAdmin();
    $immo = Immobilisation::factory()->create();
    $mvt = Mouvement::create([
        'immobilisation_id' => $immo->id,
        'type_mouvement' => 'transfert',
        'date_mouvement' => now(),
        'statut' => 'execute',
    ]);

    postJson("/api/v1/mouvements/{$mvt->id}/valider")->assertStatus(422);
});

it('valide une sortie de réforme et bascule le statut de l\'immo', function () {
    actingAsAdmin();
    $immo = Immobilisation::factory()->create(['statut' => 'actif']);
    $sortie = Sortie::create([
        'immobilisation_id' => $immo->id,
        'type_sortie' => 'reforme',
        'date_decision' => now(),
        'motif' => 'Hors service',
        'statut' => 'proposee',
    ]);

    postJson("/api/v1/sorties/{$sortie->id}/valider")->assertOk();

    expect($immo->fresh()->statut)->toBe('reforme')
        ->and($sortie->fresh()->statut)->toBe('executee');
    $this->assertDatabaseHas('mouvements', ['immobilisation_id' => $immo->id, 'type_mouvement' => 'reforme']);
});

it('valide une cession et marque l\'immo cede', function () {
    actingAsAdmin();
    $immo = Immobilisation::factory()->create(['statut' => 'actif']);
    $sortie = Sortie::create([
        'immobilisation_id' => $immo->id,
        'type_sortie' => 'cession',
        'date_decision' => now(),
        'motif' => 'Cession à un tiers',
        'prix_cession' => 50000,
        'acquereur_nom' => 'M. Test',
        'statut' => 'proposee',
    ]);

    postJson("/api/v1/sorties/{$sortie->id}/valider")->assertOk();

    expect($immo->fresh()->statut)->toBe('cede');
});
