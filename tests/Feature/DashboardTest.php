<?php

use App\Models\Immobilisation;
use Database\Seeders\CodificationSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CodificationSeeder::class);
});

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

it('retourne la répartition par catégorie', function () {
    actingAsAdmin();
    Immobilisation::factory()->count(3)->create();

    getJson('/api/v1/dashboard/patrimoine-par-categorie')
        ->assertOk()
        ->assertJsonStructure(['data' => [['code', 'libelle', 'nombre', 'valeur_brute']]]);
});
