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
            'consent_data_description' => 'Jawaban UEQ dan metadata pengisian disimpan untuk pengujian.',
            'consent_cookie_description' => 'Cookie anonim mencegah penilaian modul yang sama dua kali.',
            'consent_estimated_minutes' => 10,
            'consent_withdrawal_description' => 'Partisipasi dapat dihentikan sebelum jawaban dikirim.',
            'research_contact' => 'peneliti@example.test',
            'fast_response_seconds' => 120,
            'quality_rules_version' => 'quality-rules-v1',
            'identical_answers_flag_enabled' => true,
            'instrument_version' => 'UEQ-ID-26-v1',
        ];
    }
}
