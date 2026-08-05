<?php

namespace App\Domain\Ueq;

use InvalidArgumentException;

class UeqTransformer
{
    public function score(int $rawScore, string $positivePole): int
    {
        return match ($positivePole) {
            'right' => $rawScore - 4,
            'left' => 4 - $rawScore,
            default => throw new InvalidArgumentException('Unknown positive pole.'),
        };
    }
}
