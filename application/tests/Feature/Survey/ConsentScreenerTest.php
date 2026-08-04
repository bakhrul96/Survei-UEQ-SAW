<?php

use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyTokenService;
use App\Livewire\Survey\ConsentScreener;
use App\Models\EvaluationPeriod;
use App\Models\RespondentProfile;
use Livewire\Livewire;

it('stores consent and allows only eligible respondents', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'minimum_age' => 17]);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', true)
        ->set('age', 20)
        ->set('isIndramayuResident', true)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertRedirect();

    expect(RespondentProfile::firstOrFail()->eligible)->toBeTrue();
});

it('does not store a profile when consent or age is invalid', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', false)
        ->set('age', 16)
        ->set('isIndramayuResident', true)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertHasErrors(['consent', 'age']);

    expect(RespondentProfile::count())->toBe(0);
});

it('stores an ineligible profile and redirects to the ineligible page', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', true)
        ->set('age', 20)
        ->set('isIndramayuResident', false)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertRedirect(route('survey.ineligible', $period));

    expect(RespondentProfile::firstOrFail()->eligible)->toBeFalse();
});

it('updates the existing profile when the screener is submitted again', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);
    $issued = app(SurveyTokenService::class)->issue();

    $component = Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period]);

    $component->set('consent', true)
        ->set('age', 20)
        ->set('isIndramayuResident', true)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertRedirect();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', true)
        ->set('age', 21)
        ->set('isIndramayuResident', true)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertRedirect();

    expect(RespondentProfile::count())->toBe(1)
        ->and(RespondentProfile::firstOrFail()->age)->toBe(21);
});
