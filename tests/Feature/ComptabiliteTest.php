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
