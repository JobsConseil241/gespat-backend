<?php

use App\Models\Fournisseur;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('liste les fournisseurs (gestionnaire)', function () {
    actingAsRole('gestionnaire_patrimoine');
    Fournisseur::factory()->count(2)->create();

    getJson('/api/v1/fournisseurs')->assertOk()->assertJsonCount(2, 'data');
});

it('crée un fournisseur (gestionnaire)', function () {
    actingAsRole('gestionnaire_patrimoine');

    postJson('/api/v1/fournisseurs', [
        'raison_sociale' => 'Sogafrique Bureau',
        'ville' => 'Libreville',
        'email' => 'contact@sogafrique.ga',
    ])
        ->assertCreated()
        ->assertJsonPath('data.raison_sociale', 'Sogafrique Bureau');
});

it('refuse création par un inventoriste', function () {
    actingAsRole('inventoriste');

    postJson('/api/v1/fournisseurs', [
        'raison_sociale' => 'Interdit',
    ])->assertStatus(403);
});

it('valide email du fournisseur', function () {
    actingAsRole('gestionnaire_patrimoine');

    postJson('/api/v1/fournisseurs', [
        'raison_sociale' => 'Test',
        'email' => 'pas-un-email',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});
