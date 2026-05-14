<?php

use App\Models\CategorieImmobilisation;
use App\Models\Site;
use App\Services\Codification\CodificationService;
use Database\Seeders\CodificationSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CodificationSeeder::class);
});

it('génère des codes séquentiels uniques (mode annuel)', function () {
    $svc = app(CodificationService::class);
    $cat = CategorieImmobilisation::factory()->create(['code' => 'MAT-INF']);
    $site = Site::factory()->create(['code' => 'SIEGE']);

    $code1 = $svc->genererCode($cat, $site);
    $code2 = $svc->genererCode($cat, $site);
    $code3 = $svc->genererCode($cat, $site);

    expect($code1)->toEndWith('00001')
        ->and($code2)->toEndWith('00002')
        ->and($code3)->toEndWith('00003');
});

it('prévisualise sans incrémenter la séquence', function () {
    $svc = app(CodificationService::class);
    $cat = CategorieImmobilisation::factory()->create();
    $site = Site::factory()->create();

    $prev1 = $svc->previsualiser($cat, $site);
    $prev2 = $svc->previsualiser($cat, $site);

    expect($prev1)->toBe($prev2)->toEndWith('00001');
});

it('endpoint /codification/previsualiser retourne le prochain code', function () {
    actingAsAdmin();
    $cat = CategorieImmobilisation::factory()->create();
    $site = Site::factory()->create();

    postJson('/api/v1/codification/previsualiser', [
        'categorie_id' => $cat->id,
        'site_id' => $site->id,
    ])->assertOk()
        ->assertJsonStructure(['data' => ['code_inventaire']]);
});

it('refuse l\'accès aux plans pour un non-admin', function () {
    actingAsRole('gestionnaire_patrimoine');
    \Pest\Laravel\getJson('/api/v1/codification/plans')->assertStatus(403);
});
