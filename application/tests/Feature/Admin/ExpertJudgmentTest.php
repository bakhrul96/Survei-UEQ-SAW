<?php

use App\Application\Calculation\CalculationRunService;
use App\Application\Calculation\OfficialRunEligibility;
use App\Application\Calculation\RecordMinimumSampleDeviation;
use App\Application\Quality\RecordExpertJudgment;
use App\Models\AuditEvent;
use App\Models\EvaluationUnit;
use App\Models\ExpertJudgment;
use Illuminate\Database\QueryException;
use Tests\Support\GoldenFixture;
use Tests\Support\ReleaseTwoFixture;

beforeEach(function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $this->admin = $scenario->admin;
    $this->period = $scenario->period;
    $this->run = app(CalculationRunService::class)->preview($this->period, $this->admin);
});

it('initializes a complete operational backlog without changing saw ranking', function (): void {
    expect($this->run->expertJudgments)->toHaveCount($this->run->sawResults->count())
        ->and($this->run->expertJudgments->sortBy('operational_order')->pluck('operational_order')->values()->all())
        ->toBe(range(1, $this->run->sawResults->count()))
        ->and($this->run->expertJudgments->every(fn (ExpertJudgment $row): bool => $row->decision === 'unchanged'))
        ->toBeTrue();
});

it('moves one unit and shifts the affected backlog atomically without changing saw', function (): void {
    $beforeSaw = $this->run->sawResults()->pluck('rank', 'evaluation_unit_id')->all();
    $last = $this->run->expertJudgments->sortByDesc('operational_order')->firstOrFail();

    app(RecordExpertJudgment::class)->handle(
        $this->run,
        $last->evaluationUnit,
        1,
        'Kebutuhan regulasi harus dikerjakan lebih dahulu.',
        $this->admin,
    );

    expect($this->run->expertJudgments()->orderBy('operational_order')->pluck('operational_order')->all())
        ->toBe(range(1, $this->run->sawResults()->count()))
        ->and($this->run->sawResults()->pluck('rank', 'evaluation_unit_id')->all())
        ->toBe($beforeSaw)
        ->and($last->fresh()->decision)->toBe('adjusted')
        ->and(AuditEvent::query()->where('action', 'expert_judgment.backlog_reordered')->count())->toBe(1);
});

it('rejects invalid unit order reason and official changes', function (): void {
    $action = app(RecordExpertJudgment::class);
    $outsideUnit = EvaluationUnit::query()
        ->whereNotIn('id', $this->run->sawResults->pluck('evaluation_unit_id'))
        ->firstOrFail();
    $last = $this->run->expertJudgments->sortByDesc('operational_order')->firstOrFail();

    expect(fn () => $action->handle($this->run, $outsideUnit, 1, 'Alasan valid', $this->admin))
        ->toThrow(DomainException::class, 'Modul tidak tersedia pada hasil SAW run ini.')
        ->and(fn () => $action->handle($this->run, $last->evaluationUnit, 0, 'Alasan valid', $this->admin))
        ->toThrow(DomainException::class, 'Urutan backlog operasional harus antara 1 sampai 2.')
        ->and(fn () => $action->handle($this->run, $last->evaluationUnit, 1, '   ', $this->admin))
        ->toThrow(DomainException::class, 'Alasan keputusan expert judgment wajib diisi.');

    $official = GoldenFixture::persistedClosedRun();
    app(RecordMinimumSampleDeviation::class)->handle($official, 'Alasan fixture.', 'Notulen fixture.', $official->creator);
    $official = app(CalculationRunService::class)->lockAsOfficial($official->fresh(), $official->creator);

    expect(fn () => $action->handle(
        $official,
        $official->expertJudgments()->firstOrFail()->evaluationUnit,
        1,
        'Tidak boleh berubah.',
        $official->creator,
    ))->toThrow(DomainException::class, 'Backlog hasil resmi tidak dapat diubah.');
});

it('enforces one unique operational order per run in the database', function (): void {
    $first = $this->run->expertJudgments->firstOrFail();
    $otherUnit = EvaluationUnit::query()
        ->whereNotIn('id', $this->run->expertJudgments->pluck('evaluation_unit_id'))
        ->firstOrFail();

    expect(fn () => ExpertJudgment::query()->create([
        'calculation_run_id' => $this->run->id,
        'evaluation_unit_id' => $otherUnit->id,
        'operational_order' => $first->operational_order,
        'decision' => 'unchanged',
        'reason' => 'Duplikat untuk menguji constraint.',
        'reviewer_id' => $this->admin->id,
    ]))->toThrow(QueryException::class);
});

it('blocks official eligibility when the backlog is incomplete', function (): void {
    $run = GoldenFixture::persistedClosedRun();
    $run->expertJudgments()->firstOrFail()->delete();
    app(RecordMinimumSampleDeviation::class)->handle($run, 'Alasan fixture.', 'Notulen fixture.', $run->creator);

    expect(app(OfficialRunEligibility::class)->issues($run->fresh()))
        ->toContain('Backlog operasional harus lengkap dan berurutan sebelum hasil resmi dikunci.');
});
