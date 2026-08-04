<?php

namespace App\Domain\Study;

enum PeriodStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
    case Calculated = 'calculated';
    case Locked = 'locked';
}
