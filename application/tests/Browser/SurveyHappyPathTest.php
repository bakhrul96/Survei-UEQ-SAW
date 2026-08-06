<?php

use App\Domain\Study\PeriodStatus;

it('submits an eligible respondent evaluation on a 360 by 800 viewport', function () {
    $fixture = surveyFixture();
    $fixture->period->update([
        'status' => PeriodStatus::Active,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addDay(),
        'configuration_locked_at' => now(),
    ]);
    $fixture->unit->update(['code' => 'ibadah-yu', 'name' => 'Ibadah-Yu']);
    $fixture->period = lockStudyConfiguration($fixture->period);

    $page = visit(route('survey.entry', $fixture->period))
        ->resize(360, 800)
        ->assertSee('Informasi Penelitian')
        ->click('ui-checkbox[wire\\:model="consent"]')
        ->fill('[wire\\:model="age"]', '20')
        ->click('ui-checkbox[wire\\:model="isIndramayuResident"]')
        ->click('ui-checkbox[wire\\:model="hasUsedWongReang"]')
        ->press('Lanjutkan')
        ->waitForText('Pilih Modul')
        ->press('Ibadah-Yu')
        ->waitForText('Langkah 1 dari 4')
        ->check('[wire\\:model="confirmedExperience"]');

    foreach (range(1, 26) as $itemOrder) {
        $page->click('label[for="ueq-item-'.$itemOrder.'-value-4"]');

        if (in_array($itemOrder, [7, 14, 20], true)) {
            $page->press('Berikutnya')->waitForText('Langkah '.(array_search($itemOrder, [7, 14, 20], true) + 2).' dari 4');
        }
    }

    $page->press('Kirim Penilaian')
        ->waitForText('Penilaian berhasil disimpan')
        ->assertSee('Penilaian berhasil disimpan');
});
