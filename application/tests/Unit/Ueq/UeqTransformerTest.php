<?php

use App\Domain\Ueq\UeqTransformer;

it('transforms raw UEQ scores from the right positive pole', function (int $rawScore, int $expected): void {
    expect(app(UeqTransformer::class)->score($rawScore, 'right'))->toBe($expected);
})->with([
    [1, -3],
    [4, 0],
    [7, 3],
]);

it('transforms raw UEQ scores from the left positive pole', function (int $rawScore, int $expected): void {
    expect(app(UeqTransformer::class)->score($rawScore, 'left'))->toBe($expected);
})->with([
    [1, 3],
    [4, 0],
    [7, -3],
]);
