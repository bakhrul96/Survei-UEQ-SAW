<?php

namespace App\Livewire\Admin;

use App\Application\Reporting\ReleaseOneDashboardQuery;
use App\Models\EvaluationPeriod;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(ReleaseOneDashboardQuery $dashboard): View
    {
        $period = EvaluationPeriod::query()->firstOrFail();

        return view('livewire.admin.dashboard', [
            'period' => $period,
            'data' => $dashboard->for($period),
        ])->layout('layouts.app', ['title' => 'Dashboard Studi']);
    }
}
