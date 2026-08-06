<?php

namespace App\Livewire\Admin;

use App\Application\Calculation\CalculationRunService;
use App\Application\Calculation\OfficialRunEligibility;
use App\Application\Calculation\RecordMinimumSampleDeviation;
use App\Application\Quality\RecordExpertJudgment;
use App\Application\Reporting\SensitivityComparisonData;
use App\Application\Reporting\SensitivityComparisonQuery;
use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use DomainException;
use Illuminate\View\View;
use Livewire\Component;

class Calculations extends Component
{
    public int $periodId;

    public ?int $runId = null;

    // Expert Judgment Form State
    public ?int $selectedUnitId = null;

    public int $operationalOrder = 1;

    public string $expertReason = '';

    public string $minimumDeviationReason = '';

    public string $minimumDeviationApprovalReference = '';

    public function mount(?int $periodId = null): void
    {
        $this->periodId = $periodId ?? EvaluationPeriod::query()->firstOrFail()->id;

        $latestRun = CalculationRun::query()
            ->where('evaluation_period_id', $this->periodId)
            ->latest('id')
            ->first();

        if ($latestRun) {
            $this->runId = $latestRun->id;
        }
    }

    public function runPreview(CalculationRunService $service): void
    {
        $this->runId = $service->preview($this->period(), auth()->user())->id;
        session()->flash('status', 'Preview berhasil dibuat.');
    }

    public function lockOfficial(CalculationRunService $service): void
    {
        if (! $this->runId) {
            return;
        }

        try {
            $run = CalculationRun::query()->findOrFail($this->runId);
            $locked = $service->lockAsOfficial($run, auth()->user());
            $this->runId = $locked->id;
            session()->flash('status', 'Kalkulasi berhasil dikunci sebagai Hasil Resmi (Official).');
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function recordMinimumDeviation(RecordMinimumSampleDeviation $action): void
    {
        if (! $this->runId) {
            return;
        }

        try {
            $action->handle(
                CalculationRun::query()->findOrFail($this->runId),
                $this->minimumDeviationReason,
                $this->minimumDeviationApprovalReference,
                auth()->user(),
            );
            session()->flash('status', 'Keputusan penyimpangan minimum sampel berhasil dicatat.');
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function saveExpertJudgment(RecordExpertJudgment $action): void
    {
        if (! $this->runId || ! $this->selectedUnitId) {
            session()->flash('error', 'Pilih modul terlebih dahulu.');

            return;
        }

        try {
            $run = CalculationRun::query()->findOrFail($this->runId);
            $judgment = $run->expertJudgments()
                ->with('evaluationUnit')
                ->where('evaluation_unit_id', $this->selectedUnitId)
                ->firstOrFail();

            $action->handle(
                run: $run,
                unit: $judgment->evaluationUnit,
                operationalOrder: $this->operationalOrder,
                reason: $this->expertReason,
                reviewer: auth()->user(),
            );

            $this->reset(['expertReason', 'selectedUnitId']);
            session()->flash('status', 'Catatan Expert Judgment berhasil disimpan.');
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(
        OfficialRunEligibility $eligibility,
        SensitivityComparisonQuery $sensitivityComparisonQuery,
    ): View {
        $run = $this->runId === null
            ? null
            : CalculationRun::query()
                ->with(['creator', 'ueqResults.unit', 'ueqPooledResults', 'sawResults.unit', 'sensitivityResults.evaluationUnit', 'expertJudgments.evaluationUnit', 'lockedBy'])
                ->findOrFail($this->runId);

        $snapshot = $run?->getAttribute('input_snapshot');
        $benchmarkRows = is_array($snapshot) && is_array($snapshot['benchmarks'] ?? null)
            ? $snapshot['benchmarks']
            : [];
        $benchmarkByScale = collect($benchmarkRows)
            ->keyBy('scale')
            ->map(fn (array $row): string => $row['good_threshold']);

        $sensitivityComparison = $run === null
            ? new SensitivityComparisonData(collect(), ['S1' => false, 'S2' => false], ['S1' => [], 'S2' => []])
            : $sensitivityComparisonQuery->forRun($run);
        $sensitivityGrid = [];
        foreach ($sensitivityComparison->rows as $row) {
            if (! is_array($row) || ! is_array($row['scenarios'] ?? null)) {
                continue;
            }
            $scenarioRows = [];
            foreach ($row['scenarios'] as $scenario => $values) {
                if (! is_string($scenario) || ! is_array($values)) {
                    continue;
                }
                $scenarioRows[$scenario] = [
                    'rank' => $values['rank'] ?? null,
                    'preferenceValue' => $values['preference_value'] ?? null,
                    'deltaRank' => $values['delta_rank'] ?? null,
                    'isTied' => $values['is_tied'] ?? false,
                ];
            }
            $sensitivityGrid[$row['unit_id']] = [
                'name' => $row['unit_name'],
                'code' => $row['unit_code'],
                ...$scenarioRows,
            ];
        }

        $eligibilityIssues = $run === null ? [] : $eligibility->issues($run);

        return view('livewire.admin.calculations', [
            'period' => $this->period(),
            'run' => $run,
            'benchmarkByScale' => $benchmarkByScale,
            'sensitivityGrid' => $sensitivityGrid,
            'topThreeStable' => $sensitivityComparison->topThreeStable,
            'changedTopThreeUnitIds' => $sensitivityComparison->changedTopThreeUnitIds,
            'backlogUnits' => $run?->expertJudgments
                ->sortBy('operational_order')
                ->map(fn ($judgment) => $judgment->evaluationUnit)
                ->values() ?? collect(),
            'eligibilityIssues' => $eligibilityIssues,
            'hasMinimumSampleIssue' => collect($eligibilityIssues)->contains(
                fn (string $issue): bool => str_contains($issue, 'baru memiliki')
                    && str_contains($issue, 'respons included.'),
            ),
        ])->layout('layouts.app', ['title' => 'Kalkulasi UEQ dan SAW']);
    }

    private function period(): EvaluationPeriod
    {
        return EvaluationPeriod::query()->findOrFail($this->periodId);
    }
}
