<?php

namespace App\Livewire\Admin;

use App\Application\Calculation\CalculationRunService;
use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use Illuminate\View\View;
use Livewire\Component;

class Calculations extends Component
{
    public int $periodId;

    public ?int $runId = null;

    public function mount(): void
    {
        $this->periodId = EvaluationPeriod::query()->firstOrFail()->id;
    }

    public function runPreview(CalculationRunService $service): void
    {
        $this->runId = $service->preview($this->period(), auth()->user())->id;
        session()->flash('status', 'Preview berhasil dibuat.');
    }

    public function render(): View
    {
        $run = $this->runId === null ? null : CalculationRun::query()->with(['ueqResults.unit', 'sawResults.unit'])->findOrFail($this->runId);

        return view('livewire.admin.calculations', ['period' => $this->period(), 'run' => $run])->layout('layouts.app', ['title' => 'Kalkulasi UEQ dan SAW']);
    }

    private function period(): EvaluationPeriod
    {
        return EvaluationPeriod::query()->findOrFail($this->periodId);
    }
}
