<?php

namespace Database\Factories;

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationPeriod>
 */
class EvaluationPeriodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Periode Uji',
            'slug' => fake()->unique()->slug(2),
            'status' => PeriodStatus::Draft,
            'opens_at' => now(),
            'closes_at' => now()->addMonth(),
            'minimum_age' => 17,
            'minimum_per_unit' => 20,
            'target_per_unit' => 30,
            'target_basis' => 'Fixture pengujian',
            'consent_text' => 'Saya menyetujui partisipasi pada survei pengujian.',
            'fast_response_seconds' => 120,
            'instrument_version' => 'UEQ-ID-26-v1',
        ];
    }
}
