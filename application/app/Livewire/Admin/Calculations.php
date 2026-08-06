<?php

namespace App\Livewire\Admin;

use App\Application\Calculation\CalculationRunService;
use App\Application\Calculation\OfficialRunEligibility;
use App\Application\Calculation\RecordMinimumSampleDeviation;
use App\Application\Quality\RecordExpertJudgment;
use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
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
            $unit = EvaluationUnit::query()->findOrFail($this->selectedUnitId);

            $action->handle(
                run: $run,
                unit: $unit,
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

    public function render(OfficialRunEligibility $eligibility): View
    {
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

        $sensitivityGrid = [];
        if ($run && $run->sensitivityResults->isNotEmpty()) {
            foreach ($run->sensitivityResults as $row) {
                $unitId = $row->evaluation_unit_id;
                $sensitivityGrid[$unitId]['name'] = $row->evaluationUnit->name ?? 'Modul';
                $sensitivityGrid[$unitId]['code'] = $row->evaluationUnit->code ?? '';
                $sensitivityGrid[$unitId][$row->scenario->value] = [
                    'rank' => $row->rank,
                    'preferenceValue' => $row->preference_value,
                    'deltaRank' => $row->delta_rank,
                    'isTied' => $row->is_tied,
                ];
            }
        }

        $allUnits = EvaluationUnit::query()->orderBy('display_order')->get();
        $eligibilityIssues = $run === null ? [] : $eligibility->issues($run);

        return view('livewire.admin.calculations', [
            'period' => $this->period(),
            'run' => $run,
            'benchmarkByScale' => $benchmarkByScale,
            'sensitivityGrid' => $sensitivityGrid,
            'allUnits' => $allUnits,
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
