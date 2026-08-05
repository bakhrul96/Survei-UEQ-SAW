<?php

use App\Domain\Ueq\UeqTransformer;

function ueqTransformerFixture(): array
{
    return json_decode(
        (string) file_get_contents(__DIR__.'/../../Fixtures/ueq-saw-golden.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
}

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

it('applies the seeded polarity to every golden item mapping', function (): void {
    $transformer = app(UeqTransformer::class);

    foreach (ueqTransformerFixture()['items'] as $item) {
        expect($transformer->score(7, $item['positive_pole']))
            ->toBe($item['positive_pole'] === 'right' ? 3 : -3)
            ->and($transformer->score(1, $item['positive_pole']))
            ->toBe($item['positive_pole'] === 'right' ? -3 : 3)
            ->and($transformer->score(4, $item['positive_pole']))
            ->toBe(0);
    }
});
