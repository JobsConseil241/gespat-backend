<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CampagneInventaire>
 */
class CampagneInventaireFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'INV-'.now()->year.'-'.strtoupper(Str::random(4)),
            'libelle' => 'Campagne '.fake()->word(),
            'type' => 'partiel',
            'date_debut_prevue' => now()->addDays(7),
            'date_fin_prevue' => now()->addDays(30),
            'perimetre' => ['sites' => [], 'categories' => []],
            'statut' => 'preparation',
        ];
    }
}
