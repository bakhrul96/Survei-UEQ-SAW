<?php

namespace App\Domain\Survey;

use App\Models\AnonymousRespondent;
use Illuminate\Support\Str;
use LogicException;

class SurveyTokenService
{
    public function issue(): IssuedRespondent
    {
        $plainToken = Str::random(64);
        $respondent = AnonymousRespondent::query()->create([
            'token_hash' => $this->hash($plainToken),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        return new IssuedRespondent($respondent, $plainToken);
    }

    public function resolve(string $plainToken): ?AnonymousRespondent
    {
        $respondent = AnonymousRespondent::query()
            ->where('token_hash', $this->hash($plainToken))
            ->first();

        if ($respondent === null) {
            return null;
        }

        AnonymousRespondent::query()
            ->whereKey($respondent)
            ->update(['last_seen_at' => now()]);

        return $respondent;
    }

    public function hash(string $plainToken): string
    {
        $key = (string) config('survey.token_key');

        throw_if($key === '', LogicException::class, 'SURVEY_TOKEN_KEY wajib diatur.');

        return hash_hmac('sha256', $plainToken, $key);
    }
}
