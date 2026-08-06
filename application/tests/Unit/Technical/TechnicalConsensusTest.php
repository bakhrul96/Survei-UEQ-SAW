<?php

use App\Domain\Technical\TechnicalConsensus;
use App\Domain\Technical\TechnicalConsensusData;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ReleaseTwoFixture;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WongReangStudySeeder::class);
    $this->period = EvaluationPeriod::query()->firstOrFail();
    $this->admin = User::factory()->create();
});

it('calculates deterministic means sample deviations completeness and normalized weights', function (): void {
    ReleaseTwoFixture::saveInformant($this->period, $this->admin, 'TI-01', 4.0, 2, ['c1' => 50, 'c2' => 30, 'c3' => 20]);
    ReleaseTwoFixture::saveInformant($this->period, $this->admin, 'TI-02', 6.0, 3, ['c1' => 70, 'c2' => 10, 'c3' => 20]);
    ReleaseTwoFixture::saveInformant($this->period, $this->admin, 'TI-03', 8.0, 4, ['c1' => 60, 'c2' => 20, 'c3' => 20]);

    $unit = EvaluationUnit::query()->forWongReang()->orderBy('display_order')->firstOrFail();
    $consensus = app(TechnicalConsensus::class)->for($this->period);
    $summary = $consensus->units[$unit->id];

    expect($consensus)->toBeInstanceOf(TechnicalConsensusData::class)
        ->and($consensus->informantCount)->toBe(3)
        ->and($consensus->isComplete)->toBeTrue()
        ->and($consensus->incompleteReasons)->toBe([])
        ->and($summary->n)->toBe(3)
        ->and($summary->meanDays)->toBe(6.0)
        ->and($summary->standardDeviationDays)->toBe(2.0)
        ->and($summary->meanUrgency)->toBe(3.0)
        ->and($summary->standardDeviationUrgency)->toBe(1.0)
        ->and($consensus->weights)->toBe(['c1' => 0.6, 'c2' => 0.2, 'c3' => 0.2]);
});

it('reports an empty technical consensus as incomplete without fabricated values', function (): void {
    $unit = EvaluationUnit::query()->forWongReang()->orderBy('display_order')->firstOrFail();
    $consensus = app(TechnicalConsensus::class)->for($this->period);
    $summary = $consensus->units[$unit->id];

    expect($consensus->informantCount)->toBe(0)
        ->and($consensus->isComplete)->toBeFalse()
        ->and($consensus->incompleteReasons)->not->toBeEmpty()
        ->and($summary->n)->toBe(0)
        ->and($summary->meanDays)->toBeNull()
        ->and($summary->standardDeviationDays)->toBeNull()
        ->and($summary->meanUrgency)->toBeNull()
        ->and($summary->standardDeviationUrgency)->toBeNull()
        ->and($consensus->weights)->toBe(['c1' => null, 'c2' => null, 'c3' => null]);
});
