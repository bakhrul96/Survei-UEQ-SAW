<?php

use App\Domain\Study\PeriodStatus;
use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;

it('issues an encrypted cookie while storing only a keyed hash', function () {
    EvaluationPeriod::factory()->create(['slug' => 'riset-2026', 'status' => PeriodStatus::Active]);

    $response = $this->get('/s/wong-reang/riset-2026');

    $response->assertRedirect('/s/wong-reang/riset-2026/consent')
        ->assertCookie('ueq_survey_token');

    $respondent = AnonymousRespondent::firstOrFail();
    expect($respondent->token_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($response->getCookie('ueq_survey_token')->getValue())->not->toBe($respondent->token_hash);
});

it('reuses a valid survey token instead of creating a second respondent', function () {
    EvaluationPeriod::factory()->create(['slug' => 'riset-2026', 'status' => PeriodStatus::Active]);
    $first = $this->get('/s/wong-reang/riset-2026');
    $plainToken = $first->getCookie('ueq_survey_token')->getValue();

    $this->withCookie('ueq_survey_token', $plainToken)
        ->get('/s/wong-reang/riset-2026')
        ->assertRedirect();

    expect(AnonymousRespondent::count())->toBe(1);
});

it('does not expose consent or ineligible pages for an inactive period', function () {
    $period = EvaluationPeriod::factory()->create(['slug' => 'tertutup-2026', 'status' => PeriodStatus::Closed]);

    $this->get(route('survey.consent', $period))->assertNotFound();
    $this->get(route('survey.ineligible', $period))->assertNotFound();
});
