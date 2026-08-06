<?php

namespace Database\Factories;

use App\Models\EvaluationUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationUnit>
 */
class EvaluationUnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'display_order' => fake()->unique()->numberBetween(1000, 9999),
            'is_active' => true,
        ];
    }
}
