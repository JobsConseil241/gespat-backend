<?php

use App\Models\Etiquette;
use App\Models\Immobilisation;
use App\Models\LotEtiquettes;
use App\Services\Codification\EtiquetteService;
use Database\Seeders\CodificationSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CodificationSeeder::class);
});

it('crée un lot d\'étiquettes pour une liste d\'immobilisations', function () {
    actingAsRole('gestionnaire_patrimoine');
    $immos = Immobilisation::factory()->count(3)->create();

    $resp = postJson('/api/v1/etiquettes/lots', [
        'libelle' => 'Lot pilote',
        'format_etiquette' => 'A4_avery_24',
        'immobilisation_ids' => $immos->pluck('id')->all(),
    ])->assertCreated();

    expect($resp->json('data.nombre_etiquettes'))->toBe(3);
    expect(Etiquette::count())->toBe(3);
});

it('génère un QR payload signé HMAC', function () {
    $svc = app(EtiquetteService::class);
    $payload = $svc->buildQrPayload('TEST-CODE-001');

    expect($payload)->toContain('TEST-CODE-001')
        ->and($payload)->toContain('?sig=');

    // La signature dépend du code → un code différent doit produire une sig différente
    $payload2 = $svc->buildQrPayload('TEST-CODE-002');
    expect($payload)->not->toBe($payload2);
});

it('réédite une étiquette : code identique, ancienne marquée remplace', function () {
    actingAsRole('gestionnaire_patrimoine');
    $immo = Immobilisation::factory()->create();
    $lot = LotEtiquettes::create([
        'libelle' => 'L', 'format_etiquette' => 'A4_avery_24',
        'nombre_etiquettes' => 1,
    ]);
    $svc = app(EtiquetteService::class);
    $orig = $svc->creerEtiquette($immo->code_inventaire, $immo, $lot->id);

    $resp = postJson("/api/v1/etiquettes/{$orig->id}/reediter")->assertOk();

    $nouvelleId = $resp->json('data.id');
    $nouvelle = Etiquette::find($nouvelleId);

    // 1. code identique
    expect($nouvelle->code_inventaire)->toBe($orig->code_inventaire);
    // 2. nouvelle pointe vers l'ancienne
    expect($nouvelle->remplace_etiquette_id)->toBe($orig->id);
    // 3. ancienne marquée remplace
    expect($orig->fresh()->etat)->toBe('remplace');
});

it('marque un lot comme imprimé et bascule l\'état des étiquettes', function () {
    actingAsRole('gestionnaire_patrimoine');
    $immos = Immobilisation::factory()->count(2)->create();
    postJson('/api/v1/etiquettes/lots', [
        'libelle' => 'L', 'format_etiquette' => 'A4_avery_24',
        'immobilisation_ids' => $immos->pluck('id')->all(),
    ])->assertCreated();
    $lotId = LotEtiquettes::latest('id')->first()->id;

    postJson("/api/v1/etiquettes/lots/{$lotId}/imprime")->assertOk();

    expect(LotEtiquettes::find($lotId)->imprime_le)->not->toBeNull();
    expect(Etiquette::where('lot_id', $lotId)->where('etat', 'imprime')->count())->toBe(2);
});

it('refuse création de lot pour un inventoriste', function () {
    actingAsRole('inventoriste');
    postJson('/api/v1/etiquettes/lots', [
        'libelle' => 'X', 'format_etiquette' => 'A4_avery_24',
        'immobilisation_ids' => [1],
    ])->assertStatus(403);
});
