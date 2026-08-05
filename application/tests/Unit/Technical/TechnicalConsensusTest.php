<?php

use App\Domain\Technical\TechnicalConsensus;
use App\Models\CriteriaWeight;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\TechnicalAssessment;
use App\Models\TechnicalInformant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('averages available technical assessments and normalizes the informant allocations', function () {
    $period = EvaluationPeriod::factory()->create();
    $unit = EvaluationUnit::factory()->create(['code' => EvaluationUnit::WONG_REANG_CODES[0]]);
    $first = TechnicalInformant::query()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_code' => 'TEK-01',
    ]);
    $second = TechnicalInformant::query()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_code' => 'TEK-02',
    ]);

    TechnicalAssessment::query()->create([
        'technical_informant_id' => $first->id,
        'evaluation_unit_id' => $unit->id,
        'estimated_days' => 4.0,
        'architecture_urgency' => 2,
    ]);
    TechnicalAssessment::query()->create([
        'technical_informant_id' => $second->id,
        'evaluation_unit_id' => $unit->id,
        'estimated_days' => 8.0,
        'architecture_urgency' => 4,
    ]);
    CriteriaWeight::query()->create([
        'technical_informant_id' => $first->id,
        'c1_points' => 50,
        'c2_points' => 30,
        'c3_points' => 20,
    ]);
    CriteriaWeight::query()->create([
        'technical_informant_id' => $second->id,
        'c1_points' => 70,
        'c2_points' => 10,
        'c3_points' => 20,
    ]);

    $consensus = app(TechnicalConsensus::class)->for($period);

    expect($consensus->assessments[$unit->id])
        ->toMatchArray(['mean_days' => 6.0, 'mean_urgency' => 3.0])
        ->and($consensus->weights)->toBe([
            'c1' => 0.6,
            'c2' => 0.2,
            'c3' => 0.2,
        ]);
});

it('keeps consensus values missing when no technical data was provided', function () {
    $period = EvaluationPeriod::factory()->create();
    $unit = EvaluationUnit::factory()->create(['code' => EvaluationUnit::WONG_REANG_CODES[0]]);

    $consensus = app(TechnicalConsensus::class)->for($period);

    expect($consensus->assessments[$unit->id])
        ->toBe(['mean_days' => null, 'mean_urgency' => null])
        ->and($consensus->weights)->toBe(['c1' => null, 'c2' => null, 'c3' => null]);
});
