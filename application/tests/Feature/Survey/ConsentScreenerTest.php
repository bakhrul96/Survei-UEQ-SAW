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
