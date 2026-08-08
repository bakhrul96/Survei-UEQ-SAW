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
        ->click('input[type="checkbox"][wire\\:model\\.live="consent"]')
        ->fill('[wire\\:model="age"]', '20')
        ->click('input[type="checkbox"][wire\\:model\\.live="isIndramayuResident"]')
        ->click('input[type="checkbox"][wire\\:model\\.live="hasUsedWongReang"]')
        ->press('Lanjutkan')
        ->waitForText('Pilih Modul')
        ->press('Ibadah-Yu')
        ->waitForText('Langkah 1 dari 4');

    $page->page()->keyDown('Tab');
    $page->page()->keyUp('Tab');
    $page->assertScript("document.activeElement.matches('[wire\\\\:model\\\\.live=\"confirmedExperience\"]') && document.activeElement.className.includes('focus:ring-2')");

    $page->page()->keyDown('Tab');
    $page->page()->keyUp('Tab');
    $page->assertScript("document.activeElement.id === 'ueq-item-1-value-1' && document.activeElement.parentElement.className.includes('focus-within:ring-2')");

    foreach (range(1, 7) as $_) {
        $page->page()->keyDown('Tab');
        $page->page()->keyUp('Tab');
    }
    $page->assertScript("document.activeElement.matches('button[wire\\\\:click=\"next\"]') && document.activeElement.className.includes('focus:ring-2')")
        ->check('[wire\\:model\\.live="confirmedExperience"]');

    foreach (range(1, 26) as $itemOrder) {
        $page->click('label[for="ueq-item-'.$itemOrder.'-value-4"]');

        if (in_array($itemOrder, [7, 14, 20], true)) {
            $page->press('Berikutnya')->waitForText('Langkah '.(array_search($itemOrder, [7, 14, 20], true) + 2).' dari 4');
        }
    }

    $page->page()->keyDown('Tab');
    $page->page()->keyUp('Tab');
    $page->assertScript("document.activeElement.matches('button[wire\\\\:click=\"previous\"]') && document.activeElement.className.includes('focus:ring-2')");
    $page->page()->keyDown('Tab');
    $page->page()->keyUp('Tab');
    $page->assertScript("document.activeElement.matches('button[wire\\\\:click=\"submit\"]') && document.activeElement.className.includes('focus:ring-2')");

    $page->press('Kirim Penilaian')
        ->waitForText('Penilaian berhasil disimpan')
        ->assertSee('Penilaian berhasil disimpan')
        ->assertScript("Object.keys(localStorage).every((key) => ! key.startsWith('ueq-draft-v1:'))");
});
