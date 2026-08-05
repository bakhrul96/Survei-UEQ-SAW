<?php

namespace App\Domain\Quality;

enum QualityDecision: string
{
    case Included = 'included';
    case Excluded = 'excluded';
}
