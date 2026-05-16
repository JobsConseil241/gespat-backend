<?php

use App\Models\Site;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use OwenIt\Auditing\Models\Audit;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    // En CLI, owen-it/laravel-auditing ne crée pas d'audits automatiquement
    // (config 'console' => false). On force ici pour tester l'API.
    config(['audit.console' => true]);
});

it('refuse l\'accès aux audit logs pour un agent sans permission', function () {
    actingAsRole('inventoriste');
    getJson('/api/v1/audit/logs')->assertStatus(403);
});

it('refuse l\'accès aux stats pour un agent simple', function () {
    actingAsRole('agent_simple');
    getJson('/api/v1/audit/stats')->assertStatus(403);
});

it('admin peut lister les audit logs', function () {
    actingAsAdmin();

    getJson('/api/v1/audit/logs')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total']]);
});

it('génère un audit log à la création d\'un site', function () {
    actingAsAdmin();
    $countBefore = Audit::count();

    Site::factory()->create(['code' => 'AUDIT-TEST-1']);

    expect(Audit::count())->toBeGreaterThan($countBefore);

    $audit = Audit::where('auditable_type', Site::class)->latest('id')->first();
    expect($audit->event)->toBe('created')
        ->and($audit->new_values)->toHaveKey('code', 'AUDIT-TEST-1');
});

it('liste paginée contient au moins l\'audit créé', function () {
    actingAsAdmin();
    Site::factory()->create();

    $resp = getJson('/api/v1/audit/logs')->assertOk();
    expect($resp->json('meta.total'))->toBeGreaterThan(0);
});

it('filtre par type d\'action', function () {
    actingAsAdmin();
    $site = Site::factory()->create();
    $site->update(['libelle' => 'Modifié']);

    $created = getJson('/api/v1/audit/logs?action=created')->assertOk();
    $updated = getJson('/api/v1/audit/logs?action=updated')->assertOk();

    expect($created->json('meta.total'))->toBeGreaterThan(0)
        ->and($updated->json('meta.total'))->toBeGreaterThan(0);

    foreach ($created->json('data') as $row) {
        expect($row['event'])->toBe('created');
    }
});

it('filtre par type d\'entité (entity_type)', function () {
    actingAsAdmin();
    Site::factory()->create();
    User::factory()->create(['email' => 'tracked@gespat.local']);

    $sites = getJson('/api/v1/audit/logs?entity_type=Site')->assertOk();

    expect($sites->json('meta.total'))->toBeGreaterThan(0);
    foreach ($sites->json('data') as $row) {
        expect($row['auditable_type'])->toContain('Site');
    }
});

it('filtre par dates', function () {
    actingAsAdmin();
    Site::factory()->create();

    $today = now()->format('Y-m-d');
    $tomorrow = now()->addDay()->format('Y-m-d');

    $now = getJson("/api/v1/audit/logs?date_debut={$today}&date_fin={$today}")->assertOk();
    $futur = getJson("/api/v1/audit/logs?date_debut={$tomorrow}&date_fin={$tomorrow}")->assertOk();

    expect($now->json('meta.total'))->toBeGreaterThan(0)
        ->and($futur->json('meta.total'))->toBe(0);
});

it('endpoint /audit/stats retourne par_action + par_jour_30 + total', function () {
    actingAsAdmin();
    Site::factory()->count(3)->create();

    $resp = getJson('/api/v1/audit/stats')->assertOk();

    expect($resp->json('data'))->toHaveKeys(['par_action', 'par_jour_30', 'total'])
        ->and($resp->json('data.total'))->toBeGreaterThanOrEqual(3);
});
