<?php

namespace App\Domain\Study;

use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\UeqBenchmark;
use App\Models\UeqItem;
use Carbon\CarbonInterface;

final class StudyConfigurationHasher
{
    public function hash(EvaluationPeriod $period): string
    {
        $configuration = [
            'period' => [
                'opens_at' => $this->canonicalDate($period->opens_at),
                'closes_at' => $this->canonicalDate($period->closes_at),
                'minimum_age' => $period->minimum_age,
                'minimum_per_unit' => $period->minimum_per_unit,
                'target_per_unit' => $period->target_per_unit,
                'target_basis' => $period->target_basis,
                'consent_text' => $period->consent_text,
                'consent_data_description' => $period->consent_data_description,
                'consent_cookie_description' => $period->consent_cookie_description,
                'consent_estimated_minutes' => $period->consent_estimated_minutes,
                'consent_withdrawal_description' => $period->consent_withdrawal_description,
                'research_contact' => $period->research_contact,
                'fast_response_seconds' => $period->fast_response_seconds,
                'quality_rules_version' => $period->quality_rules_version,
                'identical_answers_flag_enabled' => $period->identical_answers_flag_enabled,
                'instrument_version' => $period->instrument_version,
                'instrument_source' => $period->instrument_source,
                'sensitivity_s1_c1' => number_format((float) $period->sensitivity_s1_c1, 6, '.', ''),
                'sensitivity_s1_c2' => number_format((float) $period->sensitivity_s1_c2, 6, '.', ''),
                'sensitivity_s1_c3' => number_format((float) $period->sensitivity_s1_c3, 6, '.', ''),
                'sensitivity_s2_c1' => number_format((float) $period->sensitivity_s2_c1, 6, '.', ''),
                'sensitivity_s2_c2' => number_format((float) $period->sensitivity_s2_c2, 6, '.', ''),
                'sensitivity_s2_c3' => number_format((float) $period->sensitivity_s2_c3, 6, '.', ''),
            ],
            'active_units' => EvaluationUnit::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get(['code', 'name', 'display_order', 'is_active'])
                ->map(fn (EvaluationUnit $unit): array => [
                    'code' => $unit->code,
                    'name' => $unit->name,
                    'display_order' => (int) $unit->display_order,
                    'is_active' => (bool) $unit->is_active,
                ])->all(),
            'instrument_items' => UeqItem::query()
                ->where('version', $period->instrument_version)
                ->orderBy('order')
                ->get(['version', 'order', 'left_label', 'right_label', 'scale', 'positive_pole'])
                ->map(fn (UeqItem $item): array => [
                    'version' => $item->version,
                    'order' => (int) $item->order,
                    'left_label' => $item->left_label,
                    'right_label' => $item->right_label,
                    'scale' => $item->scale,
                    'positive_pole' => $item->positive_pole,
                ])->all(),
            'verified_benchmarks' => UeqBenchmark::query()
                ->where('version', $period->instrument_version)
                ->whereNotNull('verified_at')
                ->orderBy('scale')
                ->get(['version', 'scale', 'good_threshold', 'source'])
                ->map(fn (UeqBenchmark $benchmark): array => [
                    'version' => $benchmark->version,
                    'scale' => $benchmark->scale,
                    'good_threshold' => number_format((float) $benchmark->good_threshold, 4, '.', ''),
                    'source' => $benchmark->source,
                ])->all(),
        ];

        $json = json_encode(
            $configuration,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
        );

        return hash('sha256', $json);
    }

    private function canonicalDate(?CarbonInterface $date): ?string
    {
        return $date?->toImmutable()->utc()->toIso8601String();
    }
}
