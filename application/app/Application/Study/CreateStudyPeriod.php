<?php

namespace App\Application\Study;

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use Illuminate\Support\Str;

final class CreateStudyPeriod
{
    /**
     * Create a new draft period. Configuration (consent, targets, quality rules,
     * sensitivity weights) is copied from the most recent period as a template so
     * the new period starts from a known-good baseline; the admin then reviews and
     * completes it before activation.
     */
    public function handle(string $name, ?string $slug, ?string $opensAt, ?string $closesAt): EvaluationPeriod
    {
        $slug = trim((string) $slug) !== '' ? trim((string) $slug) : Str::slug($name);

        $template = EvaluationPeriod::query()->latest('id')->first();

        return EvaluationPeriod::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => PeriodStatus::Draft,
            'opens_at' => $opensAt,
            'closes_at' => $closesAt,
            'minimum_age' => $template?->minimum_age ?? 17,
            'minimum_per_unit' => $template?->minimum_per_unit ?? 20,
            'target_per_unit' => $template?->target_per_unit ?? 30,
            'target_basis' => $template?->target_basis ?? '',
            'consent_text' => $template?->consent_text ?? '',
            'consent_data_description' => $template?->consent_data_description ?? '',
            'consent_cookie_description' => $template?->consent_cookie_description ?? '',
            'consent_estimated_minutes' => $template?->consent_estimated_minutes ?? 10,
            'consent_withdrawal_description' => $template?->consent_withdrawal_description ?? '',
            'research_contact' => $template?->research_contact ?? '',
            'fast_response_seconds' => $template?->fast_response_seconds ?? 120,
            'quality_rules_version' => $template?->quality_rules_version ?? 'quality-rules-v1',
            'identical_answers_flag_enabled' => $template?->identical_answers_flag_enabled ?? true,
            'instrument_version' => $template?->instrument_version ?? 'UEQ-ID-26-v1',
            'sensitivity_s1_c1' => $template?->sensitivity_s1_c1 ?? 0.60,
            'sensitivity_s1_c2' => $template?->sensitivity_s1_c2 ?? 0.20,
            'sensitivity_s1_c3' => $template?->sensitivity_s1_c3 ?? 0.20,
            'sensitivity_s2_c1' => $template?->sensitivity_s2_c1 ?? 0.20,
            'sensitivity_s2_c2' => $template?->sensitivity_s2_c2 ?? 0.40,
            'sensitivity_s2_c3' => $template?->sensitivity_s2_c3 ?? 0.40,
            'instrument_source' => $template?->instrument_source,
            'instrument_verified_at' => null,
        ]);
    }
}
