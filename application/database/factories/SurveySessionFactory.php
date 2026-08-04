<?php

namespace Database\Factories;

use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;
use App\Models\SurveySession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SurveySession>
 */
class SurveySessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'evaluation_period_id' => EvaluationPeriod::factory(),
            'anonymous_respondent_id' => AnonymousRespondent::factory(),
            'started_at' => now(),
            'last_activity_at' => now(),
            'submitted_count' => 0,
        ];
    }
}
