<?php

use App\Domain\Study\PeriodStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('submits an eligible respondent evaluation on a 360 by 800 viewport', function () {
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
        ->assertSee('Persetujuan partisipasi')
        ->check('[wire\\:model="consent"]')
        ->fill('[wire\\:model="age"]', '20')
        ->check('[wire\\:model="isIndramayuResident"]')
        ->check('[wire\\:model="hasUsedWongReang"]')
        ->press('Lanjutkan')
        ->waitForText('Pilih modul layanan')
        ->press('Ibadah-Yu')
        ->waitForText('Langkah 1 dari 4')
        ->check('[wire\\:model="confirmedExperience"]');

    foreach (range(1, 26) as $itemOrder) {
        $page->click('[aria-label="Item '.$itemOrder.' nilai 4"]');

        if (in_array($itemOrder, [7, 14, 20], true)) {
            $page->press('Lanjut')->waitForText('Langkah '.(array_search($itemOrder, [7, 14, 20], true) + 2).' dari 4');
        }
    }

    $page->press('Kirim penilaian')
        ->waitForText('Penilaian berhasil disimpan')
        ->assertSee('Penilaian berhasil disimpan');
});
