<?php

use App\Livewire\Survey\Complete;
use Livewire\Livewire;

it('shows the rated module name on the completion page', function () {
    $fixture = completedSubmissionFixture();
    $fixture->unit->update(['name' => 'Ibadah-Yu']);
    $fixture->period = lockStudyConfiguration($fixture->period);

    Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(Complete::class, ['period' => $fixture->period])
        ->assertSee('Penilaian berhasil disimpan')
        ->assertSee('Ibadah-Yu');
});
