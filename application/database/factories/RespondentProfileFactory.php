<?php

namespace Database\Factories;

use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;
use App\Models\RespondentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RespondentProfile>
 */
class RespondentProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'evaluation_period_id' => EvaluationPeriod::factory(),
            'anonymous_respondent_id' => AnonymousRespondent::factory(),
            'consented_at' => now(),
            'age' => 20,
            'is_indramayu_resident' => true,
            'has_used_wong_reang' => true,
            'eligible' => true,
            'screened_at' => now(),
        ];
    }
}
