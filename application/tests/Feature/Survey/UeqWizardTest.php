<?php

use App\Livewire\Survey\UeqWizard;
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
