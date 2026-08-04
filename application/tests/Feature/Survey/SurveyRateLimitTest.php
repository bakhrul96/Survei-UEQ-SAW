<?php

use App\Application\Survey\SubmitSurvey;
use DomainException;
use Illuminate\Support\Facades\RateLimiter;

it('blocks more than ten submit attempts per respondent per minute', function () {
    $fixture = surveyFixture();
    $key = 'survey-submit:'.$fixture->respondent->id;
    $data = validSubmitSurveyData($fixture);
    RateLimiter::clear($key);

    foreach (range(1, 10) as $attempt) {
        app(SubmitSurvey::class)->handle($data);
    }

    expect(fn () => app(SubmitSurvey::class)->handle($data))
        ->toThrow(DomainException::class, 'Terlalu banyak percobaan submit. Coba kembali dalam satu menit.');
});
