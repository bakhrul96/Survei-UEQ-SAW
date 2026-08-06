<?php

use App\Application\Calculation\CalculationRunService;
use Tests\Support\ReleaseTwoFixture;

it('keeps all release three report charts labelled focusable and responsive', function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    app(CalculationRunService::class)->preview($scenario->period, $scenario->admin);
    $this->actingAs($scenario->admin);

    $page = visit(route('admin.reports'))
        ->resize(1280, 800)
        ->waitForText('Laporan Agregat Penelitian (Bab IV)')
        ->assertVisible('[data-chart="ueq-mean"]')
        ->assertVisible('[data-chart="gap-by-scale"]')
        ->assertVisible('[data-chart="saw-contribution"]')
        ->assertVisible('[data-chart="rank-change"]')
        ->assertSee('STABIL')
        ->assertScript(<<<'JS'
            (() => Array.from(document.querySelectorAll('[data-chart]')).every((chart) =>
                chart.getAttribute('role') === 'img'
                && (chart.getAttribute('aria-label')?.trim().length ?? 0) > 0
            ))()
        JS)
        ->assertScript(<<<'JS'
            (() => Array.from(document.querySelectorAll('[data-report-table]')).every((region) =>
                region.tabIndex === 0
                && (region.getAttribute('aria-label')?.trim().length ?? 0) > 0
                && region.classList.contains('focus-visible:ring-2')
            ))()
        JS);

    $page->resize(360, 800)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors()
        ->assertNoBrokenImages();
});
