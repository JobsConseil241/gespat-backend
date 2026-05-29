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

// ===== DÉGRESSIF =====
it('calcule amortissement dégressif avec coefficient SYSCOHADA pour durée 5 ans', function () {
    $svc = app(AmortissementService::class);
    // 5 ans = coeff 2.0 SYSCOHADA → taux dégressif = 2/5 = 40%
    $immo = Immobilisation::factory()->create([
        'valeur_acquisition' => 1000000,
        'valeur_residuelle' => 0,
        'duree_amortissement_mois' => 60,
        'methode_amortissement' => 'degressif',
        'date_mise_en_service' => '2026-01-01',
    ]);

    $resultats = $svc->calculerImmobilisation($immo, 2030);

    // Année 1 : 1 000 000 × 40% = 400 000
    expect((float) $resultats[0]['dotation_exercice'])->toBe(400000.0)
        ->and((float) $resultats[0]['vnc_fin'])->toBe(600000.0);

    // Année 2 : 600 000 × 40% = 240 000 (dégressif l'emporte sur linéaire 600k/4)
    expect((float) $resultats[1]['dotation_exercice'])->toBe(240000.0);

    // Doit basculer en linéaire quand dotation linéaire restante > dégressive
    // Vérifie surtout que ça converge vers 0 sans dépasser
    $vncFinaux = collect($resultats)->pluck('vnc_fin')->map(fn ($v) => (float) $v);
    expect($vncFinaux->last())->toBeLessThanOrEqual(0.01);
});

it('applique le coefficient SYSCOHADA aux 3 bornes de durée', function () {
    $svc = app(AmortissementService::class);

    // Durée ≤ 4 ans → coeff 1.5
    $immo1 = Immobilisation::factory()->create([
        'valeur_acquisition' => 1000000, 'duree_amortissement_mois' => 48,
        'methode_amortissement' => 'degressif', 'date_mise_en_service' => '2026-01-01',
        'valeur_residuelle' => 0,
    ]);
    $r1 = $svc->calculerImmobilisation($immo1, 2026);
    // Taux dégressif annuel = 1.5/4 = 37.5% → 1 000 000 × 37.5% = 375 000
    expect((float) $r1[0]['dotation_exercice'])->toBe(375000.0);

    // Durée 5–6 → coeff 2.0
    $immo2 = Immobilisation::factory()->create([
        'valeur_acquisition' => 1000000, 'duree_amortissement_mois' => 72,
        'methode_amortissement' => 'degressif', 'date_mise_en_service' => '2026-01-01',
        'valeur_residuelle' => 0,
    ]);
    $r2 = $svc->calculerImmobilisation($immo2, 2026);
    // Taux = 2.0/6 ≈ 33.33% → environ 333 333
    expect((float) $r2[0]['dotation_exercice'])->toBeGreaterThan(330000.0)
        ->and((float) $r2[0]['dotation_exercice'])->toBeLessThan(335000.0);

    // Durée > 6 → coeff 2.5
    $immo3 = Immobilisation::factory()->create([
        'valeur_acquisition' => 1000000, 'duree_amortissement_mois' => 120,
        'methode_amortissement' => 'degressif', 'date_mise_en_service' => '2026-01-01',
        'valeur_residuelle' => 0,
    ]);
    $r3 = $svc->calculerImmobilisation($immo3, 2026);
    // Taux = 2.5/10 = 25% → 250 000
    expect((float) $r3[0]['dotation_exercice'])->toBe(250000.0);
});

// ===== VALIDATION =====
it('endpoint /amortissements/valider passe les lignes en validees', function () {
    actingAsRole('comptable');
    $immo = Immobilisation::factory()->create();
    \App\Models\AmortissementAnnuel::create([
        'immobilisation_id' => $immo->id,
        'exercice' => 2026,
        'valeur_brute_debut' => 100000,
        'cumul_amortissement_debut' => 0,
        'vnc_debut' => 100000,
        'dotation_exercice' => 33333,
        'cumul_amortissement_fin' => 33333,
        'vnc_fin' => 66667,
        'methode_utilisee' => 'lineaire',
        'est_valide' => false,
    ]);

    \Pest\Laravel\postJson('/api/v1/amortissements/valider', ['exercice' => 2026])
        ->assertOk()
        ->assertJsonPath('data.lignes_validees', 1);

    $this->assertDatabaseHas('amortissements_annuels', [
        'immobilisation_id' => $immo->id, 'exercice' => 2026, 'est_valide' => true,
    ]);
});

it('refuse validation pour un utilisateur sans permission amortissements.valider', function () {
    actingAsRole('inventoriste');
    \Pest\Laravel\postJson('/api/v1/amortissements/valider', ['exercice' => 2026])->assertStatus(403);
});
