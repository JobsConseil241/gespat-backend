<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CategorieImmobilisation>
 */
class CategorieImmobilisationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'libelle' => fake()->words(2, true),
            'classe_comptable' => fake()->randomElement(['231', '244', '245', '241']),
            'duree_amortissement_defaut' => fake()->randomElement([36, 60, 120, 240]),
            'methode_amortissement_defaut' => 'lineaire',
            'is_active' => true,
        ];
    }
}
