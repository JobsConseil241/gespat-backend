<?php

use App\Models\Immobilisation;
use App\Models\ImportComptable;
use App\Models\LigneComptableImmobilisation;
use App\Services\Comptabilite\MatchingService;
use Database\Seeders\CodificationSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CodificationSeeder::class);
});

it('matche automatiquement par numero_immobilisation_compta', function () {
    $svc = app(MatchingService::class);
    $immo = Immobilisation::factory()->create([
        'numero_immobilisation_compta' => 'COMPTA-001',
    ]);
    $import = ImportComptable::create([
        'source' => 'manuel', 'exercice' => 2026, 'date_import' => now(),
        'statut' => 'en_attente',
    ]);
    $ligne = LigneComptableImmobilisation::create([
        'import_id' => $import->id,
        'numero_immobilisation_compta' => 'COMPTA-001',
        'libelle' => 'Test',
        'valeur_origine' => 500000,
        'statut_matching' => 'non_traite',
    ]);

    $stats = $svc->executer($import);

    expect($stats['certain'])->toBe(1);
    expect($ligne->fresh()->immobilisation_id)->toBe($immo->id);
    expect($ligne->fresh()->statut_matching)->toBe('matche_auto');
    expect($ligne->fresh()->score_matching)->toBe(100);
});

it('marque non_trouve quand aucun candidat', function () {
    $svc = app(MatchingService::class);
    $import = ImportComptable::create([
        'source' => 'manuel', 'exercice' => 2026, 'date_import' => now(),
        'statut' => 'en_attente',
    ]);
    $ligne = LigneComptableImmobilisation::create([
        'import_id' => $import->id,
        'numero_immobilisation_compta' => 'INCONNU-999',
        'libelle' => 'Aucun bien correspondant',
        'valeur_origine' => 123456,
        'statut_matching' => 'non_traite',
    ]);

    $stats = $svc->executer($import);

    expect($stats['non_trouve'])->toBe(1);
    expect($ligne->fresh()->statut_matching)->toBe('non_trouve');
});

it('matching manuel attribue immobilisation_id', function () {
    actingAsRole('comptable');
    $immo = Immobilisation::factory()->create();
    $import = ImportComptable::create([
        'source' => 'manuel', 'exercice' => 2026, 'date_import' => now(),
        'statut' => 'en_attente',
    ]);
    $ligne = LigneComptableImmobilisation::create([
        'import_id' => $import->id,
        'libelle' => 'À matcher',
        'valeur_origine' => 100000,
        'statut_matching' => 'non_trouve',
    ]);

    postJson("/api/v1/comptabilite/lignes/{$ligne->id}/matcher", ['immobilisation_id' => $immo->id])
        ->assertOk();

    expect($ligne->fresh())
        ->immobilisation_id->toBe($immo->id)
        ->statut_matching->toBe('matche_manuel')
        ->score_matching->toBe(100);
});

it('affiche les écarts patrimoine vs comptabilité', function () {
    actingAsRole('comptable');
    Immobilisation::factory()->create(['est_immobilise_comptablement' => true]);

    getJson('/api/v1/comptabilite/ecarts?exercice=2026')
        ->assertOk()
        ->assertJsonStructure(['data' => ['lignes_compta_sans_immo', 'immos_sans_ligne_compta']]);
});

// ===== MATCHING PROBABLE =====
it('matche un bien par similarité libellé + valeur ±5% + date proche', function () {
    $svc = app(MatchingService::class);

    $immo = Immobilisation::factory()->create([
        'libelle' => 'Ordinateur portable Dell Latitude 5520',
        'valeur_acquisition' => 850000,
        'date_acquisition' => '2026-03-15',
        'numero_immobilisation_compta' => null,
    ]);

    $import = ImportComptable::create([
        'source' => 'manuel', 'exercice' => 2026, 'date_import' => now(),
        'statut' => 'en_attente',
    ]);
    $ligne = LigneComptableImmobilisation::create([
        'import_id' => $import->id,
        'numero_immobilisation_compta' => null,
        'libelle' => 'Ordinateur portable Dell Latitude 5520',
        'valeur_origine' => 860000, // +1.2%, dans la fenêtre ±5%
        'date_acquisition' => '2026-03-20', // +5j, dans la fenêtre ±90j
        'statut_matching' => 'non_traite',
    ]);

    $stats = $svc->executer($import);

    expect($stats['probable'])->toBe(1);
    $ligne->refresh();
    expect($ligne->immobilisation_id)->toBe($immo->id)
        ->and($ligne->statut_matching)->toBe('matche_auto')
        ->and($ligne->score_matching)->toBeGreaterThanOrEqual(80);
});

it('détecte un cas multiple quand 2 candidats correspondent', function () {
    $svc = app(MatchingService::class);

    // Deux immobilisations très similaires
    Immobilisation::factory()->create([
        'libelle' => 'Imprimante HP LaserJet',
        'valeur_acquisition' => 200000,
        'date_acquisition' => '2026-01-10',
        'numero_immobilisation_compta' => null,
    ]);
    Immobilisation::factory()->create([
        'libelle' => 'Imprimante HP LaserJet',
        'valeur_acquisition' => 202000,
        'date_acquisition' => '2026-01-12',
        'numero_immobilisation_compta' => null,
    ]);

    $import = ImportComptable::create([
        'source' => 'manuel', 'exercice' => 2026, 'date_import' => now(),
        'statut' => 'en_attente',
    ]);
    $ligne = LigneComptableImmobilisation::create([
        'import_id' => $import->id,
        'libelle' => 'Imprimante HP LaserJet',
        'valeur_origine' => 201000,
        'date_acquisition' => '2026-01-11',
        'statut_matching' => 'non_traite',
    ]);

    $stats = $svc->executer($import);

    expect($stats['multiple'])->toBe(1);
    expect($ligne->fresh()->statut_matching)->toBe('multiple');
});

// ===== ENDPOINTS =====
it('endpoint /comptabilite/imports/{import}/matcher déclenche le matching automatique', function () {
    actingAsRole('comptable');
    $immo = Immobilisation::factory()->create([
        'numero_immobilisation_compta' => 'CMP-AUTO-001',
    ]);
    $import = ImportComptable::create([
        'source' => 'manuel', 'exercice' => 2026, 'date_import' => now(),
        'statut' => 'en_attente',
    ]);
    LigneComptableImmobilisation::create([
        'import_id' => $import->id,
        'numero_immobilisation_compta' => 'CMP-AUTO-001',
        'libelle' => 'Test',
        'valeur_origine' => 100000,
        'statut_matching' => 'non_traite',
    ]);

    $resp = postJson("/api/v1/comptabilite/imports/{$import->id}/matcher")->assertOk();

    expect($resp->json('data.statistiques.certain'))->toBe(1);
});

it('endpoint /comptabilite/reconciliation génère un état avec calcul d\'écart', function () {
    actingAsRole('comptable');

    Immobilisation::factory()->create([
        'est_immobilise_comptablement' => true,
        'valeur_acquisition' => 500000,
        'date_acquisition' => '2026-01-10',
    ]);

    $import = ImportComptable::create([
        'source' => 'manuel', 'exercice' => 2026, 'date_import' => now(),
        'statut' => 'en_attente',
    ]);
    LigneComptableImmobilisation::create([
        'import_id' => $import->id,
        'libelle' => 'X', 'valeur_origine' => 600000,
        'statut_matching' => 'matche_auto',
    ]);

    $resp = postJson('/api/v1/comptabilite/reconciliation', [
        'exercice' => 2026,
        'date_arrete' => '2026-06-30',
    ])->assertOk();

    expect((float) $resp->json('data.valeur_brute_compta'))->toBe(600000.0)
        ->and((float) $resp->json('data.valeur_brute_patrimoine'))->toBe(500000.0)
        ->and((float) $resp->json('data.ecart'))->toBe(-100000.0);
});

it('refuse storeImport sans permission compta.import', function () {
    actingAsRole('inventoriste');
    \Pest\Laravel\postJson('/api/v1/comptabilite/imports', [
        'exercice' => 2026,
    ])->assertStatus(403);
});
