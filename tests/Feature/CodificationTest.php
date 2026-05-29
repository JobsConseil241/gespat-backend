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

// ===== MODES SÉQUENTIELS =====
it('mode par_categorie : séquences indépendantes par catégorie', function () {
    \App\Models\PlanCodification::where('actif', true)->update(['actif' => false]);
    $plan = \App\Models\PlanCodification::create([
        'code' => 'PLAN-CAT',
        'libelle' => 'Test par catégorie',
        'format' => '{CAT}-{SEQ:03d}',
        'separateur' => '-',
        'mode_sequentiel' => 'par_categorie',
        'actif' => true,
    ]);

    $svc = app(CodificationService::class);
    $catA = CategorieImmobilisation::factory()->create(['code' => 'CAT-A']);
    $catB = CategorieImmobilisation::factory()->create(['code' => 'CAT-B']);
    $site = Site::factory()->create();

    $a1 = $svc->genererCode($catA, $site, $plan);
    $a2 = $svc->genererCode($catA, $site, $plan);
    $b1 = $svc->genererCode($catB, $site, $plan);

    expect($a1)->toBe('CAT-A-001')
        ->and($a2)->toBe('CAT-A-002')
        ->and($b1)->toBe('CAT-B-001'); // séquence indépendante
});

it('mode par_site : séquences indépendantes par site', function () {
    \App\Models\PlanCodification::where('actif', true)->update(['actif' => false]);
    $plan = \App\Models\PlanCodification::create([
        'code' => 'PLAN-SITE',
        'libelle' => 'Test par site',
        'format' => '{SITE}-{SEQ:03d}',
        'separateur' => '-',
        'mode_sequentiel' => 'par_site',
        'actif' => true,
    ]);

    $svc = app(CodificationService::class);
    $cat = CategorieImmobilisation::factory()->create();
    $siteA = Site::factory()->create(['code' => 'SITE-A']);
    $siteB = Site::factory()->create(['code' => 'SITE-B']);

    expect($svc->genererCode($cat, $siteA, $plan))->toBe('SITE-A-001');
    expect($svc->genererCode($cat, $siteA, $plan))->toBe('SITE-A-002');
    expect($svc->genererCode($cat, $siteB, $plan))->toBe('SITE-B-001');
});

it('mode global : une seule séquence partagée', function () {
    \App\Models\PlanCodification::where('actif', true)->update(['actif' => false]);
    $plan = \App\Models\PlanCodification::create([
        'code' => 'PLAN-GLOB',
        'libelle' => 'Test global',
        'format' => 'G-{SEQ:04d}',
        'separateur' => '-',
        'mode_sequentiel' => 'global',
        'actif' => true,
    ]);

    $svc = app(CodificationService::class);
    $catA = CategorieImmobilisation::factory()->create(['code' => 'X']);
    $catB = CategorieImmobilisation::factory()->create(['code' => 'Y']);
    $siteA = Site::factory()->create(['code' => 'S1']);
    $siteB = Site::factory()->create(['code' => 'S2']);

    expect($svc->genererCode($catA, $siteA, $plan))->toBe('G-0001');
    expect($svc->genererCode($catB, $siteB, $plan))->toBe('G-0002');
    expect($svc->genererCode($catA, $siteB, $plan))->toBe('G-0003');
});

it('endpoint POST /codification/plans crée et active un nouveau plan', function () {
    actingAsAdmin();
    \Pest\Laravel\postJson('/api/v1/codification/plans', [
        'code' => 'PLAN-NEW',
        'libelle' => 'Nouveau plan',
        'format' => '{ORG}-{SEQ:05d}',
        'separateur' => '-',
        'mode_sequentiel' => 'annuel',
        'actif' => true,
    ])->assertCreated();

    $actifs = \App\Models\PlanCodification::where('actif', true)->count();
    expect($actifs)->toBe(1);
    expect(\App\Models\PlanCodification::where('actif', true)->first()->code)->toBe('PLAN-NEW');
});
