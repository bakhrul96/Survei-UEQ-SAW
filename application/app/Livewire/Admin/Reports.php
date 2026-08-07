<?php

namespace App\Livewire\Admin;

use App\Application\Reporting\AggregateReportQuery;
use App\Models\EvaluationPeriod;
use Illuminate\View\View;
use Livewire\Component;

class Reports extends Component
{
    public ?int $periodId = null;

    public function mount(?int $periodId = null): void
    {
        $this->periodId = $periodId ?? EvaluationPeriod::query()->first()?->id;
    }

    public function render(AggregateReportQuery $query): View
    {
        if ($this->periodId === null) {
            return view('livewire.admin.empty-period')
                ->layout('layouts.app', ['title' => 'Laporan Agregat Penelitian']);
        }

        $period = EvaluationPeriod::query()->findOrFail($this->periodId);
        $reportData = $query->for($period);

        return view('livewire.admin.reports', [
            'period' => $period,
            'reportData' => $reportData,
        ])->layout('layouts.app', ['title' => 'Laporan Agregat Penelitian']);
    }
}
