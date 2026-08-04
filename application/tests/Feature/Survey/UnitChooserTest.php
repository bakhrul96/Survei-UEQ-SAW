<?php

use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyTokenService;
use App\Livewire\Survey\UnitChooser;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\RespondentProfile;
use App\Models\SurveySession;
use App\Models\SurveySubmission;
use Livewire\Livewire;

it('shows active units and marks an already submitted unit complete', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);
    $unit = EvaluationUnit::factory()->create(['name' => 'Ibadah-Yu']);
    $issued = app(SurveyTokenService::class)->issue();
    RespondentProfile::factory()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_respondent_id' => $issued->respondent->id,
        'eligible' => true,
    ]);
    SurveySubmission::factory()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_respondent_id' => $issued->respondent->id,
        'evaluation_unit_id' => $unit->id,
        'status' => 'submitted',
    ]);

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(UnitChooser::class, ['period' => $period])
        ->assertSee('Ibadah-Yu')
        ->assertSee('Sudah dinilai');
});

it('rejects direct selection by an ineligible respondent', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(UnitChooser::class, ['period' => $period])
        ->assertForbidden();
});

it('starts a session and redirects an eligible respondent to an available unit', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);
    $unit = EvaluationUnit::factory()->create(['code' => 'ibadah-yu']);
    $issued = app(SurveyTokenService::class)->issue();
    RespondentProfile::factory()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_respondent_id' => $issued->respondent->id,
        'eligible' => true,
    ]);

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(UnitChooser::class, ['period' => $period])
        ->call('choose', $unit->id)
        ->assertRedirect(route('survey.wizard', ['period' => $period, 'unit' => $unit->code]));

    expect(SurveySession::query()->where([
        'evaluation_period_id' => $period->id,
        'anonymous_respondent_id' => $issued->respondent->id,
    ])->count())->toBe(1);
});

it('rejects selection of inactive and submitted units', function (bool $isActive, bool $submitted) {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);
    $unit = EvaluationUnit::factory()->create(['is_active' => $isActive]);
    $issued = app(SurveyTokenService::class)->issue();
    RespondentProfile::factory()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_respondent_id' => $issued->respondent->id,
        'eligible' => true,
    ]);

    if ($submitted) {
        SurveySubmission::factory()->create([
            'evaluation_period_id' => $period->id,
            'anonymous_respondent_id' => $issued->respondent->id,
            'evaluation_unit_id' => $unit->id,
        ]);
    }

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(UnitChooser::class, ['period' => $period])
        ->call('choose', $unit->id)
        ->assertForbidden();
})->with([
    'inactive unit' => [false, false],
    'submitted unit' => [true, true],
]);
