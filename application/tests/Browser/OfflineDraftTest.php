<?php

use App\Domain\Study\PeriodStatus;

it('keeps a UEQ draft when the browser reports an offline interruption', function () {
    $fixture = surveyFixture();
    $fixture->period->update([
        'status' => PeriodStatus::Active,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addDay(),
        'configuration_locked_at' => now(),
    ]);
    $fixture->unit->update(['code' => 'ibadah-yu', 'name' => 'Ibadah-Yu']);

    $page = visit(route('survey.entry', $fixture->period))
        ->resize(360, 800)
        ->click('ui-checkbox[wire\\:model="consent"]')
        ->fill('[wire\\:model="age"]', '20')
        ->click('ui-checkbox[wire\\:model="isIndramayuResident"]')
        ->click('ui-checkbox[wire\\:model="hasUsedWongReang"]')
        ->press('Lanjutkan')
        ->waitForText('Pilih Modul')
        ->press('Ibadah-Yu')
        ->waitForText('Langkah 1 dari 4')
        ->check('[wire\\:model="confirmedExperience"]')
        ->click('label[for="ueq-item-1-value-4"]')
        ->wait(1)
        ->assertScript("Object.keys(localStorage).some((key) => key.startsWith('ueq-draft-v1:') && JSON.parse(localStorage.getItem(key)).answers['1'] === '4')");

    $page->script("() => { window.dispatchEvent(new Event('offline')); return true; }");
    $page->assertSee('Koneksi terputus');

    $page->page()->reload();
    $page->waitForText('Langkah 1 dari 4');
    $page->page()->waitForFunction("() => document.querySelector('#ueq-item-1-value-4')?.checked === true");
    $page->assertChecked('#ueq-item-1-value-4');
});
