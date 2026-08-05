<?php

use App\Domain\Saw\SawAlternative;
use App\Domain\Saw\SawCalculator;
use App\Domain\Saw\SawResultData;

it('normalizes all-zero gaps to zero', function (): void {
    $rows = app(SawCalculator::class)->rank([
        new SawAlternative('ibadah-yu', 1, 0.0, 4.0, 3.0),
        new SawAlternative('info-yu', 2, 0.0, 8.0, 5.0),
    ], ['c1' => .4, 'c2' => .3, 'c3' => .3]);

    expect(collect($rows)->every(fn (SawResultData $row): bool => $row->r1 === 0.0))->toBeTrue();
});

it('rejects a zero estimate and gives equal preference values the same rank', function (): void {
    expect(fn (): array => app(SawCalculator::class)->rank([
        new SawAlternative('ibadah-yu', 1, 1.0, 0.0, 3.0),
        new SawAlternative('info-yu', 2, 1.0, 8.0, 5.0),
    ], ['c1' => .4, 'c2' => .3, 'c3' => .3]))
        ->toThrow(DomainException::class, 'estimated_days');

    $rows = app(SawCalculator::class)->rank([
        new SawAlternative('ibadah-yu', 1, 1.0, 4.0, 3.0),
        new SawAlternative('info-yu', 2, 1.0, 4.0, 3.0),
    ], ['c1' => .4, 'c2' => .3, 'c3' => .3]);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->rank)->toBe(1)
        ->and($rows[1]->rank)->toBe(1)
        ->and($rows[0]->isTied)->toBeTrue()
        ->and($rows[1]->isTied)->toBeTrue();
});
