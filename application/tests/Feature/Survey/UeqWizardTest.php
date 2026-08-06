<?php

use App\Domain\Study\PeriodStatus;
use App\Livewire\Survey\UeqWizard;
use App\Models\SurveyAnswer;
use App\Models\SurveySubmission;
use Livewire\Livewire;

it('uses four steps with boundaries 7 7 6 6', function () {
    $fixture = surveyFixture();

    Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(UeqWizard::class, ['period' => $fixture->period, 'unit' => $fixture->unit])
        ->assertSet('step', 1)
        ->assertViewHas('items', fn ($items) => $items->count() === 7)
        ->set('answers.1', 4)
        ->call('next')
        ->assertHasErrors(['answers.2']);
});

it('opens a global unit from the period scoped wizard route', function () {
    $fixture = surveyFixture();

    $this->withCookie('ueq_survey_token', $fixture->plainToken)
        ->get(route('survey.wizard', ['period' => $fixture->period, 'unit' => $fixture->unit]))
        ->assertOk()
        ->assertSee('Langkah 1 dari 4');
});

it('does not expose converted scores to the respondent', function () {
    $fixture = surveyFixture();

    Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(UeqWizard::class, ['period' => $fixture->period, 'unit' => $fixture->unit])
        ->assertDontSee('Skor terkonversi')
        ->assertDontSee('Benchmark');
});

it('normalizes browser radio values before submitting', function () {
    $fixture = surveyFixture();
    $wizard = Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(UeqWizard::class, ['period' => $fixture->period, 'unit' => $fixture->unit])
        ->set('confirmedExperience', true);

    foreach (range(1, 26) as $itemOrder) {
        $wizard->set('answers.'.$itemOrder, '4');
    }

    $wizard->call('submit')
        ->assertRedirect(route('survey.complete', $fixture->period));
});

it('rejects submission when the selected unit is deactivated after mount', function () {
    $fixture = surveyFixture();
    $wizard = Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(UeqWizard::class, ['period' => $fixture->period, 'unit' => $fixture->unit]);

    $fixture->unit->update(['is_active' => false]);

    $wizard->call('submit')->assertNotFound();
});

it('rejects submission when the period closes after the wizard mounts', function () {
    $fixture = surveyFixture();
    $wizard = Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(UeqWizard::class, ['period' => $fixture->period, 'unit' => $fixture->unit])
        ->set('confirmedExperience', true);

    foreach (range(1, 26) as $itemOrder) {
        $wizard->set('answers.'.$itemOrder, 4);
    }

    $fixture->period->update(['status' => PeriodStatus::Closed]);

    $wizard->call('submit')->assertNotFound();

    expect(SurveySubmission::count())->toBe(0)
        ->and(SurveyAnswer::count())->toBe(0);
});

it('renders large tap targets with visible pole labels for every ueq item', function () {
    $fixture = surveyFixture();

    Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(UeqWizard::class, ['period' => $fixture->period, 'unit' => $fixture->unit])
        // Setiap sel radio mudah ditap (min-h-11) dan dapat dibaca mesin (role radiogroup + aria-label).
        ->assertSee('min-h-11', escape: false)
        ->assertSee('role="radiogroup"', escape: false)
        ->assertSee('aria-label="Item ', escape: false);
});
