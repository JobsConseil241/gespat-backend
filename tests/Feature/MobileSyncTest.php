<?php

use App\Models\CampagneInventaire;
use App\Models\FicheInventaire;
use App\Models\Immobilisation;
use App\Models\Site;
use Database\Seeders\CodificationSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CodificationSeeder::class);
});

function setupCampagneAvecFiches(int $nbFiches = 3): array
{
    $site = Site::factory()->create();
    $immos = Immobilisation::factory()->count($nbFiches)->create(['site_id' => $site->id, 'statut' => 'actif']);
    $campagne = CampagneInventaire::factory()->create([
        'perimetre' => ['sites' => [$site->id]],
        'statut' => 'en_cours',
    ]);

    foreach ($immos as $immo) {
        FicheInventaire::create([
            'campagne_id' => $campagne->id,
            'immobilisation_id' => $immo->id,
            'code_attendu' => $immo->code_inventaire,
            'statut' => 'a_inventorier',
            'version' => 1,
        ]);
    }

    return [$campagne, $immos];
}

it('pull renvoie la campagne et ses fiches', function () {
    actingAsRole('inventoriste');
    [$campagne] = setupCampagneAvecFiches(2);

    $resp = getJson("/api/v1/sync/campagnes/{$campagne->id}/pull?device_id=tablet-1")
        ->assertOk();

    expect($resp->json('data.campagne.id'))->toBe($campagne->id)
        ->and($resp->json('data.fiches'))->toHaveCount(2);
});

it('scanner reconnaît un bien existant et trouve sa fiche', function () {
    actingAsRole('inventoriste');
    [$campagne, $immos] = setupCampagneAvecFiches(1);

    $resp = postJson("/api/v1/sync/campagnes/{$campagne->id}/scanner", [
        'code_inventaire' => $immos[0]->code_inventaire,
    ])->assertOk();

    expect($resp->json('data.immobilisation_trouvee'))->toBeTrue()
        ->and($resp->json('data.action_suggeree'))->toBe('inventorier');
});

it('scanner d\'un code inconnu propose l\'action code_inconnu', function () {
    actingAsRole('inventoriste');
    [$campagne] = setupCampagneAvecFiches(1);

    postJson("/api/v1/sync/campagnes/{$campagne->id}/scanner", [
        'code_inventaire' => 'CODE-INEXISTANT-XYZ',
    ])->assertOk()
        ->assertJsonPath('data.immobilisation_trouvee', false)
        ->assertJsonPath('data.action_suggeree', 'code_inconnu');
});

it('push enregistre une fiche inventoriée et bump la version', function () {
    actingAsRole('inventoriste');
    [$campagne] = setupCampagneAvecFiches(1);
    $fiche = $campagne->fiches()->first();

    postJson('/api/v1/sync/push', [
        'device_id' => 'tablet-1',
        'campagne_id' => $campagne->id,
        'fiches' => [[
            'id' => $fiche->id,
            'statut' => 'vu_conforme',
            'etat_constate' => 'bon',
            'inventorie_at' => now()->toIso8601String(),
            'version_locale' => 1,
        ]],
    ])->assertOk()
        ->assertJsonPath('data.accepted', 1)
        ->assertJsonPath('data.conflicts', []);

    $fiche->refresh();
    expect($fiche->statut)->toBe('vu_conforme')
        ->and($fiche->version)->toBe(2);
});

it('push détecte un conflit de version', function () {
    actingAsRole('inventoriste');
    [$campagne] = setupCampagneAvecFiches(1);
    $fiche = $campagne->fiches()->first();
    $fiche->update(['version' => 5]); // serveur en avance

    $resp = postJson('/api/v1/sync/push', [
        'device_id' => 'tablet-1',
        'campagne_id' => $campagne->id,
        'fiches' => [[
            'id' => $fiche->id,
            'statut' => 'vu_conforme',
            'inventorie_at' => now()->toIso8601String(),
            'version_locale' => 1,
        ]],
    ])->assertOk();

    expect($resp->json('data.accepted'))->toBe(0)
        ->and($resp->json('data.conflicts'))->toHaveCount(1)
        ->and($resp->json('data.conflicts.0.raison'))->toBe('version_obsolete');
});

it('push crée une fiche de découverte pour un bien sans fiche pré-générée', function () {
    actingAsRole('inventoriste');
    [$campagne] = setupCampagneAvecFiches(0);
    $immo = Immobilisation::factory()->create();

    $resp = postJson('/api/v1/sync/push', [
        'device_id' => 'tablet-1',
        'campagne_id' => $campagne->id,
        'fiches' => [[
            'code_scanne' => $immo->code_inventaire,
            'statut' => 'decouverte',
            'inventorie_at' => now()->toIso8601String(),
        ]],
    ])->assertOk()
        ->assertJsonPath('data.accepted', 1);

    expect($resp->json('data.created_ids'))->toHaveCount(1);
    $this->assertDatabaseHas('fiches_inventaire', [
        'campagne_id' => $campagne->id,
        'immobilisation_id' => $immo->id,
        'statut' => 'decouverte',
    ]);
});
