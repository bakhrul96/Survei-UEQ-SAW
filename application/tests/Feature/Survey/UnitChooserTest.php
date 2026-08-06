<?php

use App\Application\Survey\SubmitSurvey;
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
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $unit = EvaluationUnit::factory()->create(['name' => 'Ibadah-Yu']);
    $period = lockStudyConfiguration($period);
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
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $period = lockStudyConfiguration($period);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(UnitChooser::class, ['period' => $period])
        ->assertForbidden();
});

it('starts a session and redirects an eligible respondent to an available unit', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $unit = EvaluationUnit::factory()->create(['code' => 'ibadah-yu']);
    $period = lockStudyConfiguration($period);
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
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);
    $unit = EvaluationUnit::factory()->create(['is_active' => $isActive]);
    $period = lockStudyConfiguration($period);
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

it('uses a new session for another module after inactivity with the same token', function () {
    $fixture = surveyFixture();
    $firstUnit = $fixture->unit;
    $secondUnit = EvaluationUnit::factory()->create(['code' => 'unit-lanjutan']);
    $fixture->period = lockStudyConfiguration($fixture->period);

    app(SubmitSurvey::class)->handle(validSubmitSurveyData($fixture));

    $this->travel(31)->minutes();

    Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(UnitChooser::class, ['period' => $fixture->period])
        ->call('choose', $secondUnit->id)
        ->assertRedirect(route('survey.wizard', ['period' => $fixture->period, 'unit' => $secondUnit->code]));

    $fixture->session = SurveySession::query()->latest('started_at')->firstOrFail();
    $fixture->unit = $secondUnit;
    app(SubmitSurvey::class)->handle(validSubmitSurveyData($fixture));

    expect(SurveySession::count())->toBe(2)
        ->and(SurveySubmission::query()->where('evaluation_unit_id', $firstUnit->id)->count())->toBe(1)
        ->and(SurveySubmission::query()->where('evaluation_unit_id', $secondUnit->id)->count())->toBe(1);

    Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(UnitChooser::class, ['period' => $fixture->period])
        ->call('choose', $firstUnit->id)
        ->assertForbidden();
});
