<?php

use App\Models\UeqResult;
use Tests\Support\GoldenFixture;

it('persists the complete golden calculation from raw answers to ranked results', function (): void {
    $fixture = GoldenFixture::data();
    $run = GoldenFixture::persistedRun();

    expect($run->input_hash)->toHaveLength(64)
        ->and($run->included_count)->toBe(8)
        ->and($run->excluded_count)->toBe(2)
        ->and($run->sawResults)->toHaveCount(2)
        ->and($run->ueqPooledResults)->toHaveCount(6);

    foreach ($fixture['expected']['gaps'] as $unitCode => $expectedScales) {
        foreach ($expectedScales as $scale => $expectedGap) {
            $persisted = $run->ueqResults->first(
                fn (UeqResult $row): bool => $row->unit->code === $unitCode && $row->scale === $scale,
            );
            expect($persisted)->not->toBeNull()
                ->and((float) $persisted->gap)->toEqualWithDelta($expectedGap, $fixture['tolerance']);
        }
    }

    foreach ($fixture['expected']['saw'] as $unitCode => $expected) {
        if ($unitCode === 'weights') {
            continue;
        }
        $persisted = $run->sawResults->first(fn ($row): bool => $row->unit->code === $unitCode);
        expect((float) $persisted->preference_value)->toEqualWithDelta($expected['vi'], $fixture['tolerance'])
            ->and($persisted->rank)->toBe($expected['rank'])
            ->and($persisted->is_tied)->toBe($expected['is_tied']);
    }

    $snapshot = $run->input_snapshot;
    expect($snapshot['items'][0])->toHaveKeys(['positive_pole', 'scale', 'order'])
        ->and($snapshot['benchmarks'][0])->toHaveKeys(['version', 'source', 'good_threshold', 'verified_at'])
        ->and($snapshot['technical_informants'])->toHaveCount(3)
        ->and($snapshot['technical_consensus']['is_complete'])->toBeTrue()
        ->and($snapshot['technical_consensus']['units'])->toHaveCount(13)
        ->and($snapshot['quality_decisions'])->toHaveCount(10)
        ->and($snapshot['included_raw_answers'])->toHaveCount(8);

    foreach ($fixture['expected']['saw']['weights'] as $criterion => $expectedWeight) {
        expect($snapshot['technical_consensus']['weights'][$criterion])
            ->toEqualWithDelta($expectedWeight, $fixture['tolerance']);
    }
});
