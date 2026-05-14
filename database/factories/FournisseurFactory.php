<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fournisseur>
 */
class FournisseurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'raison_sociale' => fake()->company(),
            'niu' => 'P'.fake()->numerify('######').strtoupper(fake()->randomLetter()),
            'contact' => fake()->name(),
            'adresse' => fake()->streetAddress(),
            'ville' => fake()->city(),
            'pays' => 'Gabon',
            'telephone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'is_active' => true,
        ];
    }
}
