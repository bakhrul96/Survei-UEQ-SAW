<?php

use App\Application\Calculation\CalculationInputSnapshot;
use App\Application\Calculation\CalculationRunService;
use App\Application\Calculation\SawResultWriter;
use App\Domain\Technical\SaveTechnicalAssessment;
use App\Models\AuditEvent;
use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\TechnicalInformant;
use App\Models\UeqBenchmark;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;
use Tests\Support\ReleaseTwoFixture;

beforeEach(function (): void {
    $this->seed(WongReangStudySeeder::class);
    $this->period = EvaluationPeriod::query()->firstOrFail();
    $this->admin = User::factory()->create();
});

it('rejects a partial informant before any row is written', function (): void {
    $assessments = ReleaseTwoFixture::completeAssessments();
    array_pop($assessments);

    expect(fn () => app(SaveTechnicalAssessment::class)->handle(
        $this->period,
        'TI-01',
        $assessments,
        ['c1' => 40, 'c2' => 30, 'c3' => 30],
        $this->admin,
    ))->toThrow(DomainException::class, 'tepat 13 modul');

    expect(TechnicalInformant::count())->toBe(0);
});

it('rejects a sixth informant but permits updating an existing code', function (): void {
    ReleaseTwoFixture::seedInformants($this->period, $this->admin, 5);

    expect(fn () => ReleaseTwoFixture::saveInformant($this->period, $this->admin, 'TI-06'))
        ->toThrow(DomainException::class, 'maksimal lima informan')
        ->and(fn () => ReleaseTwoFixture::saveInformant($this->period, $this->admin, 'TI-05'))
        ->not->toThrow(DomainException::class);
});

it('rejects invalid values through the domain service', function (float $days, int $urgency, array $weights): void {
    $assessments = ReleaseTwoFixture::completeAssessments(days: $days, urgency: $urgency);

    expect(fn () => app(SaveTechnicalAssessment::class)->handle(
        $this->period,
        'TI-01',
        $assessments,
        $weights,
        $this->admin,
    ))->toThrow(DomainException::class);
})->with([
    'zero days' => [0.0, 3, ['c1' => 40, 'c2' => 30, 'c3' => 30]],
    'urgency six' => [1.0, 6, ['c1' => 40, 'c2' => 30, 'c3' => 30]],
    'weight ninety' => [1.0, 3, ['c1' => 40, 'c2' => 30, 'c3' => 20]],
]);

it('increments input revision stales previews and audits an informant update', function (): void {
    ReleaseTwoFixture::seedInformants($this->period, $this->admin, 3);
    $revision = $this->period->fresh()->calculation_input_revision;
    $run = CalculationRun::query()->create([
        'evaluation_period_id' => $this->period->id,
        'algorithm_version' => CalculationRunService::ALGORITHM_VERSION,
        'status' => 'preview',
        'input_hash' => str_repeat('b', 64),
        'input_snapshot' => [],
        'warnings' => [],
        'included_count' => 0,
        'excluded_count' => 0,
        'created_by' => $this->admin->id,
        'calculated_at' => now(),
    ]);

    ReleaseTwoFixture::saveInformant($this->period, $this->admin, 'TI-01', 2.0, 4);

    expect($this->period->fresh()->calculation_input_revision)->toBe($revision + 1)
        ->and($run->fresh()->status)->toBe('stale')
        ->and(AuditEvent::query()->where('action', 'technical_assessment.updated')->count())->toBe(4);
});

it('stores complete technical consensus including sample deviations in the calculation snapshot', function (): void {
    UeqBenchmark::query()->update(['verified_at' => now()]);
    ReleaseTwoFixture::saveInformant($this->period, $this->admin, 'TI-01', 4.0, 2);
    ReleaseTwoFixture::saveInformant($this->period, $this->admin, 'TI-02', 6.0, 3);
    ReleaseTwoFixture::saveInformant($this->period, $this->admin, 'TI-03', 8.0, 4);

    $unit = EvaluationUnit::query()->forWongReang()->orderBy('display_order')->firstOrFail();
    $snapshot = app(CalculationInputSnapshot::class)->for($this->period->fresh(), CalculationRunService::ALGORITHM_VERSION);
    $consensus = $snapshot['technical_consensus'];

    expect($consensus['informant_count'])->toBe(3)
        ->and($consensus['is_complete'])->toBeTrue()
        ->and($consensus['units'][(string) $unit->id]['n'])->toBe(3)
        ->and($consensus['units'][(string) $unit->id]['mean_days'])->toBe(6.0)
        ->and($consensus['units'][(string) $unit->id]['standard_deviation_days'])->toBe(2.0);
});

it('blocks saw when technical consensus is incomplete', function (): void {
    $result = app(SawResultWriter::class)->calculate([
        'technical_consensus' => [
            'informant_count' => 2,
            'is_complete' => false,
            'incomplete_reasons' => ['Jumlah informan lengkap harus 3 sampai 5.'],
            'units' => [],
            'weights' => ['c1' => 0.4, 'c2' => 0.3, 'c3' => 0.3],
        ],
    ], []);

    expect($result['rows'])->toBe([])
        ->and($result['warnings'])->toBe(['SAW belum dihitung: konsensus teknis 3–5 informan belum lengkap.']);
});

it('uses snapshot consensus means as saw technical criteria', function (): void {
    $units = EvaluationUnit::query()->forWongReang()->orderBy('display_order')->limit(2)->get();
    $consensusUnits = [];
    foreach ($units->values() as $index => $unit) {
        $consensusUnits[(string) $unit->id] = [
            'unit_id' => $unit->id,
            'n' => 3,
            'mean_days' => 5.0 + $index,
            'standard_deviation_days' => 1.0,
            'mean_urgency' => 3.0 + $index,
            'standard_deviation_urgency' => 1.0,
        ];
    }
    $ueqRows = $units->flatMap(fn (EvaluationUnit $unit) => collect(range(1, 6))->map(fn (): array => [
        'evaluation_unit_id' => $unit->id,
        'gap' => 0.5,
    ]))->values()->all();

    $result = app(SawResultWriter::class)->calculate([
        'technical_consensus' => [
            'informant_count' => 3,
            'is_complete' => true,
            'incomplete_reasons' => [],
            'units' => $consensusUnits,
            'weights' => ['c1' => 0.4, 'c2' => 0.3, 'c3' => 0.3],
        ],
    ], $ueqRows);

    expect($result['rows'])->toHaveCount(2)
        ->and($result['rows'][0]['x2_days'])->toBeIn([5.0, 6.0])
        ->and(collect($result['rows'])->pluck('x2_days')->sort()->values()->all())->toBe([5.0, 6.0])
        ->and(collect($result['rows'])->pluck('x3_urgency')->sort()->values()->all())->toBe([3.0, 4.0]);
});
