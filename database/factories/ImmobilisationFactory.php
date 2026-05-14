<?php

namespace Database\Factories;

use App\Models\CategorieImmobilisation;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Immobilisation>
 */
class ImmobilisationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code_inventaire' => 'TST-MAT-FCT-2026-'.fake()->unique()->numerify('#####'),
            'libelle' => 'Bien de test '.Str::random(5),
            'description' => fake()->sentence(),
            'categorie_id' => CategorieImmobilisation::factory(),
            'site_id' => Site::factory(),
            'date_acquisition' => fake()->dateTimeBetween('-3 years', 'now'),
            'mode_acquisition' => 'achat',
            'valeur_acquisition' => fake()->numberBetween(50000, 5000000),
            'devise' => 'XAF',
            'est_immobilise_comptablement' => true,
            'methode_amortissement' => 'lineaire',
            'duree_amortissement_mois' => 36,
            'date_mise_en_service' => fake()->dateTimeBetween('-2 years', 'now'),
            'etat_physique' => 'bon',
            'statut' => 'actif',
        ];
    }
}
