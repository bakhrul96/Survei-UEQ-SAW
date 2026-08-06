<?php

use App\Application\Calculation\CalculationRunService;
use App\Livewire\Admin\Reports;
use App\Models\EvaluationUnit;
use App\Models\SensitivityResult;
use Livewire\Livewire;
use Tests\Support\ReleaseTwoFixture;

beforeEach(function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $this->admin = $scenario->admin;
    $this->period = $scenario->period;
    $this->run = app(CalculationRunService::class)->preview($this->period, $this->admin);

    $outsideUnit = EvaluationUnit::query()
        ->whereNotIn('id', $this->run->sawResults->pluck('evaluation_unit_id'))
        ->firstOrFail();
    SensitivityResult::query()->create([
        'calculation_run_id' => $this->run->id,
        'scenario' => 'S1',
        'evaluation_unit_id' => $outsideUnit->id,
        'preference_value' => 0.5,
        'rank' => 3,
        'delta_rank' => 0,
        'is_tied' => false,
    ]);
});

it('requires authentication for reports page', function (): void {
    $this->get('/admin/reports')->assertRedirect('/login');
});

it('renders all accessible release three charts numeric tables and stability labels', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Reports::class, ['periodId' => $this->period->id])
        ->assertSeeHtml('data-chart="ueq-mean"')
        ->assertSeeHtml('data-chart="gap-by-scale"')
        ->assertSeeHtml('data-chart="saw-contribution"')
        ->assertSeeHtml('data-chart="rank-change"')
        ->assertSee('Tabel angka UEQ')
        ->assertSee('Tabel kontribusi SAW')
        ->assertSee('STABIL')
        ->assertSee('BERUBAH');
});

it('does not select stale runs as report fallback', function (): void {
    $this->run->update(['status' => 'stale']);

    Livewire::actingAs($this->admin)
        ->test(Reports::class, ['periodId' => $this->period->id])
        ->assertSee('BELUM ADA KALKULASI')
        ->assertDontSee($this->run->input_hash);
});
