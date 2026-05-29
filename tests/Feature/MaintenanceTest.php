<?php

use App\Models\Immobilisation;
use App\Models\Maintenance;
use Database\Seeders\CodificationSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CodificationSeeder::class);
});

it('liste les maintenances (vide)', function () {
    actingAsRole('gestionnaire_patrimoine');

    getJson('/api/v1/maintenances')->assertOk()->assertJsonStructure(['data']);
});

it('crée une maintenance préventive', function () {
    actingAsRole('gestionnaire_patrimoine');
    $immo = Immobilisation::factory()->create();

    postJson('/api/v1/maintenances', [
        'immobilisation_id' => $immo->id,
        'type' => 'preventive',
        'date_prevue' => '2026-06-15',
        'prochaine_echeance' => '2027-06-15',
        'cout' => 50000,
        'prestataire' => 'Société XYZ',
    ])->assertCreated()
        ->assertJsonPath('data.statut', 'planifiee')
        ->assertJsonPath('data.type', 'preventive');
});

it('refuse création de maintenance sans permission immobilisations.update', function () {
    actingAsRole('inventoriste');
    $immo = Immobilisation::factory()->create();

    postJson('/api/v1/maintenances', [
        'immobilisation_id' => $immo->id,
        'type' => 'curative',
    ])->assertStatus(403);
});

it('met à jour le statut et la date de réalisation', function () {
    actingAsRole('gestionnaire_patrimoine');
    $immo = Immobilisation::factory()->create();
    $m = Maintenance::create([
        'immobilisation_id' => $immo->id,
        'type' => 'preventive',
        'date_prevue' => '2026-06-01',
        'statut' => 'planifiee',
    ]);

    putJson("/api/v1/maintenances/{$m->id}", [
        'statut' => 'terminee',
        'date_realisation' => '2026-06-02',
        'cout' => 75000,
    ])->assertOk()
        ->assertJsonPath('data.statut', 'terminee');

    expect((float) $m->fresh()->cout)->toBe(75000.0);
});

it('supprime une maintenance', function () {
    actingAsRole('gestionnaire_patrimoine');
    $immo = Immobilisation::factory()->create();
    $m = Maintenance::create([
        'immobilisation_id' => $immo->id,
        'type' => 'curative', 'statut' => 'planifiee',
    ]);

    deleteJson("/api/v1/maintenances/{$m->id}")->assertOk();
    $this->assertDatabaseMissing('maintenances', ['id' => $m->id]);
});

it('filtre par échéances proches (≤30j)', function () {
    actingAsRole('gestionnaire_patrimoine');
    $immo = Immobilisation::factory()->create();
    Maintenance::create([
        'immobilisation_id' => $immo->id, 'type' => 'preventive',
        'prochaine_echeance' => now()->addDays(15), 'statut' => 'planifiee',
    ]);
    Maintenance::create([
        'immobilisation_id' => $immo->id, 'type' => 'preventive',
        'prochaine_echeance' => now()->addDays(60), 'statut' => 'planifiee',
    ]);

    $resp = getJson('/api/v1/maintenances?echeances_proches=1')->assertOk();
    expect(count($resp->json('data.data') ?? $resp->json('data')))->toBe(1);
});
