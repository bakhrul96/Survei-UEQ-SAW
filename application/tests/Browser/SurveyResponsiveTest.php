<?php

use App\Domain\Study\PeriodStatus;

it('keeps every survey page inside 360 pixels without overflow or accessibility issues', function () {
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
        ->waitForText('Informasi Penelitian')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues(1)
        ->click('input[type="checkbox"][wire\\:model\\.live="consent"]')
        ->fill('[wire\\:model="age"]', '20')
        ->click('input[type="checkbox"][wire\\:model\\.live="isIndramayuResident"]')
        ->click('input[type="checkbox"][wire\\:model\\.live="hasUsedWongReang"]')
        ->press('Lanjutkan')
        ->waitForText('Pilih Modul')
        ->press('Ibadah-Yu')
        ->waitForText('Langkah 1 dari 4')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues(1)
        ->check('[wire\\:model\\.live="confirmedExperience"]');

    $step = 1;

    foreach (range(1, 26) as $itemOrder) {
        $page->click('label[for="ueq-item-'.$itemOrder.'-value-4"]');

        if (in_array($itemOrder, [7, 14, 20, 26], true)) {
            $page->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
                ->assertNoAccessibilityIssues(1);

            if ($step < 4) {
                $page->press('Berikutnya')->waitForText('Langkah '.($step + 1).' dari 4');
            }

            $step++;
        }
    }

    $page->press('Kirim Penilaian')
        ->waitForText('Penilaian berhasil disimpan')
        ->assertSee('Penilaian berhasil disimpan')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoAccessibilityIssues(1)
        ->assertNoJavaScriptErrors();
});
