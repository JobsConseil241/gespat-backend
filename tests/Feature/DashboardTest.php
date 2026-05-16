<?php

use App\Models\AmortissementAnnuel;
use App\Models\CampagneInventaire;
use App\Models\CategorieImmobilisation;
use App\Models\Immobilisation;
use App\Models\Site;
use Database\Seeders\CodificationSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CodificationSeeder::class);
});

// ===== KPI =====
it('retourne les KPI patrimoine', function () {
    actingAsAdmin();
    Immobilisation::factory()->count(5)->create(['statut' => 'actif', 'valeur_acquisition' => 100000]);
    Immobilisation::factory()->count(2)->create(['statut' => 'reforme', 'valeur_acquisition' => 50000]);

    $resp = getJson('/api/v1/dashboard/kpi')->assertOk();
    expect($resp->json('data.biens.total'))->toBe(7)
        ->and($resp->json('data.biens.actifs'))->toBe(5)
        ->and($resp->json('data.biens.sortis'))->toBe(2)
        ->and((float) $resp->json('data.valorisation.valeur_brute_acquisition'))->toBe(600000.0);
});

it('refuse dashboard pour un agent sans permission reports.view', function () {
    actingAsRole('agent_simple');
    getJson('/api/v1/dashboard/kpi')->assertStatus(403);
});

it('KPI ne compte pas les immobilisations soft-deleted', function () {
    actingAsAdmin();
    $immos = Immobilisation::factory()->count(3)->create(['statut' => 'actif', 'valeur_acquisition' => 100000]);
    $immos->first()->delete();

    $resp = getJson('/api/v1/dashboard/kpi')->assertOk();
    expect($resp->json('data.biens.total'))->toBe(2);
});

it('KPI affiche le nombre de campagnes en cours', function () {
    actingAsAdmin();
    CampagneInventaire::factory()->count(2)->create(['statut' => 'en_cours']);
    CampagneInventaire::factory()->create(['statut' => 'preparation']);
    CampagneInventaire::factory()->create(['statut' => 'cloturee']);

    $resp = getJson('/api/v1/dashboard/kpi')->assertOk();
    expect($resp->json('data.inventaire.campagnes_en_cours'))->toBe(2);
});

it('KPI agrège la VNC totale depuis amortissements_annuels', function () {
    actingAsAdmin();
    $immo = Immobilisation::factory()->create();
    AmortissementAnnuel::create([
        'immobilisation_id' => $immo->id,
        'exercice' => now()->year,
        'valeur_brute_debut' => 1000000,
        'cumul_amortissement_debut' => 0,
        'vnc_debut' => 1000000,
        'dotation_exercice' => 250000,
        'cumul_amortissement_fin' => 250000,
        'vnc_fin' => 750000,
        'methode_utilisee' => 'lineaire',
    ]);

    $resp = getJson('/api/v1/dashboard/kpi')->assertOk();
    expect((float) $resp->json('data.valorisation.vnc_totale_exercice_courant'))->toBe(750000.0);
});

// ===== Répartitions =====
it('retourne la répartition par catégorie', function () {
    actingAsAdmin();
    Immobilisation::factory()->count(3)->create();

    getJson('/api/v1/dashboard/patrimoine-par-categorie')
        ->assertOk()
        ->assertJsonStructure(['data' => [['code', 'libelle', 'nombre', 'valeur_brute']]]);
});

it('agrège correctement la valeur brute par catégorie', function () {
    actingAsAdmin();
    $cat = CategorieImmobilisation::factory()->create(['code' => 'TST-AGG']);
    Immobilisation::factory()->count(2)->create([
        'categorie_id' => $cat->id, 'valeur_acquisition' => 50000,
    ]);

    $resp = getJson('/api/v1/dashboard/patrimoine-par-categorie')->assertOk();
    $row = collect($resp->json('data'))->firstWhere('code', 'TST-AGG');

    expect($row)->not->toBeNull()
        ->and((int) $row['nombre'])->toBe(2)
        ->and((float) $row['valeur_brute'])->toBe(100000.0);
});

it('retourne la répartition par site', function () {
    actingAsAdmin();
    $site = Site::factory()->create(['code' => 'TST-SITE']);
    Immobilisation::factory()->count(3)->create(['site_id' => $site->id, 'valeur_acquisition' => 75000]);

    $resp = getJson('/api/v1/dashboard/patrimoine-par-site')->assertOk();
    $row = collect($resp->json('data'))->firstWhere('code', 'TST-SITE');

    expect($row)->not->toBeNull()
        ->and((int) $row['nombre'])->toBe(3)
        ->and((float) $row['valeur_brute'])->toBe(225000.0);
});

it('retourne la répartition par statut', function () {
    actingAsAdmin();
    Immobilisation::factory()->count(4)->create(['statut' => 'actif']);
    Immobilisation::factory()->count(1)->create(['statut' => 'reforme']);

    $resp = getJson('/api/v1/dashboard/patrimoine-par-statut')->assertOk();
    $data = collect($resp->json('data'));

    expect((int) $data->firstWhere('statut', 'actif')['nombre'])->toBe(4)
        ->and((int) $data->firstWhere('statut', 'reforme')['nombre'])->toBe(1);
});

it('refuse répartition par site pour un agent sans permission', function () {
    actingAsRole('agent_simple');
    getJson('/api/v1/dashboard/patrimoine-par-site')->assertStatus(403);
});
