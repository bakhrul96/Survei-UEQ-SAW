<?php

use Tests\Support\ReleaseThreeFixture;

it('exercises the complete immutable release three workflow', function (): void {
    $scenario = ReleaseThreeFixture::eligibleScenario();
    $firstSaw = $scenario->run->sawResults->sortBy('rank')->firstOrFail();
    $s1 = $scenario->run->sensitivityResults
        ->where('evaluation_unit_id', $firstSaw->evaluation_unit_id)
        ->first(fn ($row): bool => $row->scenario->value === 'S1');
    $lastBacklog = $scenario->run->expertJudgments->sortByDesc('operational_order')->firstOrFail();
    $hash = $scenario->run->input_hash;
    $vi = number_format((float) $firstSaw->preference_value, 6);
    $s1Rank = '#'.$s1->rank;
    $this->actingAs($scenario->admin);

    $page = visit(route('admin.calculations'))
        ->resize(1280, 800)
        ->waitForText('Kalkulasi UEQ dan SAW')
        ->assertVisible('[data-testid="sensitivity-results"]')
        ->assertSee('S0')
        ->assertSee('S1')
        ->assertSee('S2')
        ->assertSee('STABIL')
        ->assertSee($hash)
        ->assertSee($vi)
        ->assertSee($s1Rank)
        ->select('[data-testid="backlog-unit"]', (string) $lastBacklog->evaluation_unit_id)
        ->fill('[data-testid="backlog-order"]', '1')
        ->fill('[data-testid="backlog-reason"]', 'Kebutuhan regulasi harus diprioritaskan pada backlog operasional.')
        ->click('[data-testid="backlog-save"]')
        ->waitForText('Catatan Expert Judgment berhasil disimpan.')
        ->click('[data-testid="lock-official"]')
        ->waitForText('OFFICIAL / LOCKED')
        ->refresh()
        ->waitForText('OFFICIAL / LOCKED')
        ->assertSee($hash)
        ->assertSee($vi)
        ->assertSee($s1Rank)
        ->assertNotPresent('[data-testid="backlog-save"]')
        ->assertNotPresent('[data-testid="lock-official"]')
        ->click('[data-flux-sidebar-item][href$="/admin/reports"]')
        ->waitForText('Laporan Agregat Penelitian (Bab IV)')
        ->assertVisible('[data-chart="ueq-mean"]')
        ->assertVisible('[data-chart="gap-by-scale"]')
        ->assertVisible('[data-chart="saw-contribution"]')
        ->assertVisible('[data-chart="rank-change"]')
        ->assertSee('Peringkat Analitis SAW vs Backlog Operasional')
        ->assertVisible('[data-testid="export-xlsx"]')
        ->assertVisible('[data-testid="export-csv"]')
        ->assertNoJavaScriptErrors()
        ->assertNoBrokenImages();
});
