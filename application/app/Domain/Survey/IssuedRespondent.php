<?php

namespace App\Domain\Survey;

use App\Models\AnonymousRespondent;

readonly class IssuedRespondent
{
    public function __construct(
        public AnonymousRespondent $respondent,
        public string $plainToken,
    ) {}
}
