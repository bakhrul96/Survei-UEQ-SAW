<?php

namespace Database\Factories;

use App\Models\AnonymousRespondent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnonymousRespondent>
 */
class AnonymousRespondentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ];
    }
}
