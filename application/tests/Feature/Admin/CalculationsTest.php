<?php

use App\Application\Calculation\CalculationRunService;
use App\Livewire\Admin\Calculations;
use Livewire\Livewire;
use Tests\Support\ReleaseTwoFixture;

beforeEach(function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $this->admin = $scenario->admin;
    $this->period = $scenario->period;
});

it('protects the calculation preview page', function (): void {
    $this->get('/admin/calculations')->assertRedirect('/login');
});

it('renders the calculation controls and release three features', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Calculations::class, ['periodId' => $this->period->id])
        ->assertSee('Jalankan preview')
        ->call('runPreview')
        ->assertSee('Kunci Hasil Resmi (Official)')
        ->assertSee('Analisis Sensitivitas Peringkat (S0 vs S1 vs S2)')
        ->assertSee('Expert Judgment & Backlog Operasional');
});

it('allows locking a calculation run as official', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Calculations::class, ['periodId' => $this->period->id])
        ->call('runPreview')
        ->call('lockOfficial')
        ->assertSee('OFFICIAL / LOCKED');
});

it('shows complete run ueq reliability and saw evidence without private inputs', function (): void {
    $run = app(CalculationRunService::class)->preview($this->period, $this->admin);

    Livewire::actingAs($this->admin)
        ->test(Calculations::class, ['periodId' => $run->evaluation_period_id])
        ->set('runId', $run->id)
        ->assertSee('Dibuat oleh')
        ->assertSee('Waktu kalkulasi')
        ->assertSee('CI 95% bawah')
        ->assertSee('CI 95% atas')
        ->assertSee('Batas Good')
        ->assertSee('Pooled reliability')
        ->assertSee('Kontribusi C1')
        ->assertSee('Kontribusi C2')
        ->assertSee('Kontribusi C3')
        ->assertDontSee('included_raw_answers')
        ->assertDontSee('anonymous_respondent_id')
        ->assertDontSee('token_hash')
        ->assertDontSee('user_agent');
});
