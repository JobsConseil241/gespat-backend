<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Site>
 */
class SiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'libelle' => fake()->company(),
            'type' => fake()->randomElement(['siege', 'agence', 'depot', 'site_distant']),
            'adresse' => fake()->streetAddress(),
            'ville' => fake()->city(),
            'province' => fake()->randomElement(['Estuaire', 'Haut-Ogooué', 'Moyen-Ogooué', 'Ngounié', 'Nyanga', 'Ogooué-Ivindo', 'Ogooué-Lolo', 'Ogooué-Maritime', 'Woleu-Ntem']),
            'latitude' => fake()->latitude(-4, 3),
            'longitude' => fake()->longitude(8, 14),
            'is_active' => true,
        ];
    }
}
