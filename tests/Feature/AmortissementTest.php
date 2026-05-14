<?php

use App\Models\Immobilisation;
use App\Services\Amortissement\AmortissementService;
use Database\Seeders\CodificationSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CodificationSeeder::class);
});

it('calcule amortissement linéaire avec prorata temporis', function () {
    $svc = app(AmortissementService::class);
    $immo = Immobilisation::factory()->create([
        'valeur_acquisition' => 1200000,
        'valeur_residuelle' => 0,
        'duree_amortissement_mois' => 36,
        'methode_amortissement' => 'lineaire',
        'date_mise_en_service' => '2026-04-01',
    ]);

    $resultats = $svc->calculerImmobilisation($immo, 2029);

    // Année 1 : prorata 9/36 mois = 300 000
    expect($resultats[0]['exercice'])->toBe(2026)
        ->and((float) $resultats[0]['dotation_exercice'])->toBe(300000.0);

    // Année 2 et 3 : dotation pleine = 400 000
    expect((float) $resultats[1]['dotation_exercice'])->toBe(400000.0)
        ->and((float) $resultats[2]['dotation_exercice'])->toBe(400000.0);

    // Année 4 : reliquat = 100 000
    expect((float) $resultats[3]['dotation_exercice'])->toBe(100000.0)
        ->and((float) $resultats[3]['vnc_fin'])->toBe(0.0);
});

it('ne calcule rien si non amortissable', function () {
    $svc = app(AmortissementService::class);
    $immo = Immobilisation::factory()->create([
        'methode_amortissement' => 'non_amortissable',
        'date_mise_en_service' => null,
    ]);

    expect($svc->calculerImmobilisation($immo, 2030))->toBeEmpty();
});

it('persiste les amortissements en base', function () {
    $svc = app(AmortissementService::class);
    $immo = Immobilisation::factory()->create([
        'valeur_acquisition' => 600000,
        'duree_amortissement_mois' => 36,
        'methode_amortissement' => 'lineaire',
        'date_mise_en_service' => '2026-01-01',
    ]);

    $count = $svc->persister($immo, 2028);

    expect($count)->toBe(3);
    $this->assertDatabaseCount('amortissements_annuels', 3);
});

it('endpoint /amortissements/simuler retourne le tableau', function () {
    actingAsRole('gestionnaire_patrimoine');
    $immo = Immobilisation::factory()->create([
        'valeur_acquisition' => 360000,
        'duree_amortissement_mois' => 36,
        'methode_amortissement' => 'lineaire',
        'date_mise_en_service' => '2026-01-01',
    ]);

    $resp = \Pest\Laravel\postJson('/api/v1/amortissements/simuler', [
        'immobilisation_id' => $immo->id,
        'exercice_max' => 2028,
    ])->assertOk();

    expect($resp->json('data'))->toHaveCount(3);
});

it('endpoint /amortissements/calculer/{immo} (avec persistance) requiert la permission', function () {
    actingAsRole('inventoriste');
    $immo = Immobilisation::factory()->create();

    \Pest\Laravel\postJson("/api/v1/amortissements/calculer/{$immo->id}")->assertStatus(403);
});
