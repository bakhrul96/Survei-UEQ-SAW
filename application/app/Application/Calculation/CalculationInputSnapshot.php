<?php

namespace App\Application\Calculation;

use App\Domain\Quality\QualityDecision;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\SurveySubmission;
use App\Models\UeqBenchmark;
use App\Models\UeqItem;
use DomainException;
use JsonException;

class CalculationInputSnapshot
{
    /** @return array<string, mixed> */
    public function for(EvaluationPeriod $period, string $algorithmVersion): array
    {
        $items = UeqItem::query()
            ->where('version', $period->instrument_version)
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id', 'version', 'order', 'left_label', 'right_label', 'scale', 'positive_pole'])
            ->map(fn (UeqItem $item): array => [
                'id' => $item->id,
                'version' => $item->version,
                'order' => $item->order,
                'left_label' => $item->left_label,
                'right_label' => $item->right_label,
                'scale' => $item->scale,
                'positive_pole' => $item->positive_pole,
            ])->all();
        $scales = collect($items)->pluck('scale')->unique()->sort()->values()->all();
        $benchmarks = UeqBenchmark::query()
            ->where('version', $period->instrument_version)
            ->orderBy('scale')
            ->orderBy('id')
            ->get(['id', 'version', 'scale', 'good_threshold', 'source', 'verified_at']);

        $benchmarkSnapshot = [];
        foreach ($scales as $scale) {
            $benchmark = $benchmarks->firstWhere('scale', $scale);

            if ($benchmark === null) {
                throw new DomainException("Benchmark {$scale} tidak ditemukan.");
            }

            if (! filled($benchmark->source) || $benchmark->verified_at === null) {
                throw new DomainException("Benchmark {$scale} harus memiliki sumber dan waktu verifikasi.");
            }

            $benchmarkSnapshot[] = [
                'id' => $benchmark->id,
                'version' => $benchmark->version,
                'scale' => $benchmark->scale,
                'good_threshold' => (string) $benchmark->good_threshold,
                'source' => $benchmark->source,
                'verified_at' => $benchmark->verified_at->toIso8601String(),
            ];
        }

        $submissions = SurveySubmission::query()
            ->where('evaluation_period_id', $period->id)
            ->with(['answers' => fn ($query) => $query->orderBy('item_order'), 'qualityReview'])
            ->orderBy('id')
            ->get();

        $includedIds = [];
        $excludedIds = [];
        $includedRawAnswers = [];
        $qualityDecisions = [];
        $warnings = [];

        foreach ($submissions as $submission) {
            $review = $submission->qualityReview;
            $decision = $review?->decision;
            $decisionValue = $review === null ? 'unreviewed' : $decision->value;
            $qualityDecisions[] = [
                'submission_id' => $submission->id,
                'evaluation_unit_id' => $submission->evaluation_unit_id,
                'decision' => $decisionValue,
                'reason' => $review?->reason,
                'flags' => $review?->flags,
                'reviewed_by' => $review?->reviewed_by,
                'reviewed_at' => $review?->reviewed_at?->toIso8601String(),
            ];

            if ($decision === QualityDecision::Included) {
                $includedIds[] = $submission->id;
                $includedRawAnswers[(string) $submission->id] = $submission->answers
                    ->mapWithKeys(fn ($answer): array => [(string) $answer->item_order => $answer->raw_score])
                    ->all();
            } elseif ($decision === QualityDecision::Excluded) {
                $excludedIds[] = $submission->id;
            } else {
                $warnings[] = "Submission {$submission->id} belum direview dan tidak disertakan.";
            }
        }

        $units = EvaluationUnit::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'display_order', 'is_active'])
            ->map(fn (EvaluationUnit $unit): array => [
                'id' => $unit->id,
                'code' => $unit->code,
                'name' => $unit->name,
                'display_order' => $unit->display_order,
                'is_active' => $unit->is_active,
            ])->all();

        return $this->canonicalize([
            'algorithm_version' => $algorithmVersion,
            'configuration' => [
                'evaluation_period_id' => $period->id,
                'instrument_version' => $period->instrument_version,
                'minimum_per_unit' => $period->minimum_per_unit,
                'target_per_unit' => $period->target_per_unit,
                'fast_response_seconds' => $period->fast_response_seconds,
                'calculation_input_revision' => $period->calculation_input_revision,
            ],
            'items' => $items,
            'benchmarks' => $benchmarkSnapshot,
            'units' => $units,
            'quality_decisions' => $qualityDecisions,
            'included_submission_ids' => $includedIds,
            'excluded_submission_ids' => $excludedIds,
            'included_raw_answers' => $includedRawAnswers,
            'warnings' => $warnings,
        ]);
    }

    /** @param array<string, mixed> $snapshot */
    public function hash(array $snapshot): string
    {
        try {
            return hash('sha256', json_encode($this->canonicalize($snapshot), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
        } catch (JsonException $exception) {
            throw new DomainException('Input kalkulasi tidak dapat di-hash.', previous: $exception);
        }
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $child) {
            $value[$key] = $this->canonicalize($child);
        }

        if (array_is_list($value)) {
            usort($value, function (mixed $left, mixed $right): int {
                if (is_array($left) && is_array($right) && array_key_exists('id', $left) && array_key_exists('id', $right)) {
                    return $left['id'] <=> $right['id'];
                }

                return json_encode($left, JSON_PRESERVE_ZERO_FRACTION) <=> json_encode($right, JSON_PRESERVE_ZERO_FRACTION);
            });

            return $value;
        }

        ksort($value, SORT_STRING);

        return $value;
    }
}
