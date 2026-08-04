<?php

namespace Database\Factories;

use App\Models\UeqItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UeqItem>
 */
class UeqItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'version' => 'UEQ-TEST-'.fake()->uuid(),
            'order' => 1,
            'left_label' => 'kiri',
            'right_label' => 'kanan',
            'scale' => 'Attractiveness',
            'positive_pole' => 'right',
        ];
    }
}
