<?php

namespace Database\Factories;

use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\SurveySession;
use App\Models\SurveySubmission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SurveySubmission>
 */
class SurveySubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'evaluation_period_id' => EvaluationPeriod::factory(),
            'anonymous_respondent_id' => AnonymousRespondent::factory(),
            'survey_session_id' => SurveySession::factory(),
            'evaluation_unit_id' => EvaluationUnit::factory(),
            'idempotency_key' => (string) Str::uuid(),
            'instrument_version' => 'UEQ-ID-26-v1',
            'status' => 'submitted',
            'started_at' => now()->subMinutes(4),
            'completed_at' => now(),
            'duration_seconds' => 240,
            'session_sequence' => 1,
        ];
    }
}
