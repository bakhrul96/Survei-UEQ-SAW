<?php

namespace App\Livewire\Admin;

use App\Domain\Study\PeriodReadinessService;
use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use App\Models\UeqBenchmark;
use DomainException;
use Illuminate\View\View;
use Livewire\Component;

class StudySettings extends Component
{
    public int $periodId;
    public string $opensAt = '';
    public string $closesAt = '';
    public int $minimumAge;
    public int $minimumPerUnit;
    public int $targetPerUnit;
    public string $targetBasis = '';
    public string $consentText = '';
    public string $instrumentSource = '';

    public function mount(): void
    {
        $period = EvaluationPeriod::query()->firstOrFail();

        $this->periodId = $period->id;
        $this->fillFromPeriod($period);
    }

    public function save(): void
    {
        $period = $this->period();
        abort_unless($period->status === PeriodStatus::Draft, 403);

        $validated = $this->validate([
            'opensAt' => ['required', 'date'],
            'closesAt' => ['required', 'date', 'after:opensAt'],
            'minimumAge' => ['required', 'integer', 'min:17'],
            'minimumPerUnit' => ['required', 'integer', 'min:1'],
            'targetPerUnit' => ['required', 'integer', 'gte:minimumPerUnit'],
            'targetBasis' => ['required', 'string'],
            'consentText' => ['required', 'string'],
            'instrumentSource' => ['nullable', 'string'],
        ]);

        $period->update([
            'opens_at' => $validated['opensAt'],
            'closes_at' => $validated['closesAt'],
            'minimum_age' => $validated['minimumAge'],
            'minimum_per_unit' => $validated['minimumPerUnit'],
            'target_per_unit' => $validated['targetPerUnit'],
            'target_basis' => trim($validated['targetBasis']),
            'consent_text' => trim($validated['consentText']),
            'instrument_source' => trim((string) $validated['instrumentSource']) ?: null,
        ]);

        $this->fillFromPeriod($period->fresh());
    }

    public function activate(PeriodReadinessService $readiness): void
    {
        $period = $this->period();
        abort_unless($period->status === PeriodStatus::Draft, 403);

        try {
            $activated = $readiness->activate($period);
            $this->fillFromPeriod($activated);
            session()->flash('status', 'Periode berhasil diaktifkan dan konfigurasi dikunci.');
        } catch (DomainException $exception) {
            $this->addError('activation', $exception->getMessage());
        }
    }

    public function render(PeriodReadinessService $readiness): View
    {
        $period = $this->period();

        return view('livewire.admin.study-settings', [
            'period' => $period,
            'issues' => $readiness->issues($period),
            'isDraft' => $period->status === PeriodStatus::Draft,
            'benchmarks' => UeqBenchmark::query()->orderBy('scale')->get(),
        ])->layout('layouts.app', ['title' => 'Pengaturan Studi']);
    }

    private function period(): EvaluationPeriod
    {
        return EvaluationPeriod::query()->findOrFail($this->periodId);
    }

    private function fillFromPeriod(EvaluationPeriod $period): void
    {
        $this->opensAt = $period->opens_at?->format('Y-m-d\\TH:i') ?? '';
        $this->closesAt = $period->closes_at?->format('Y-m-d\\TH:i') ?? '';
        $this->minimumAge = $period->minimum_age;
        $this->minimumPerUnit = $period->minimum_per_unit;
        $this->targetPerUnit = $period->target_per_unit;
        $this->targetBasis = $period->target_basis;
        $this->consentText = $period->consent_text;
        $this->instrumentSource = $period->instrument_source ?? '';
    }
}
