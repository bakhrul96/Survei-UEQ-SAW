<?php

use Tests\Support\ReleaseTwoFixture;

it('associates every expert judgment label with its control', function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $this->actingAs($scenario->admin);

    visit(route('admin.calculations'))
        ->resize(1280, 800)
        ->waitForText('Kalkulasi UEQ dan SAW')
        ->press('Jalankan preview')
        ->waitForText('Expert Judgment & Backlog Operasional')
        ->assertScript(<<<'JS'
            (() => {
                const control = document.getElementById('expert-unit');

                return control !== null
                    && document.querySelector('label[for="expert-unit"]') !== null
                    && control.labels?.length === 1;
            })()
        JS)
        ->assertScript(<<<'JS'
            (() => {
                const control = document.getElementById('expert-operational-order');

                return control !== null
                    && document.querySelector('label[for="expert-operational-order"]') !== null
                    && control.labels?.length === 1;
            })()
        JS)
        ->assertScript(<<<'JS'
            (() => {
                const control = document.getElementById('expert-reason');

                return control !== null
                    && document.querySelector('label[for="expert-reason"]') !== null
                    && control.labels?.length === 1;
            })()
        JS);
});

it('makes every calculation table a named keyboard region', function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $this->actingAs($scenario->admin);

    $page = visit(route('admin.calculations'))
        ->resize(1280, 800)
        ->waitForText('Kalkulasi UEQ dan SAW')
        ->press('Jalankan preview')
        ->waitForText('Pooled reliability');

    $page->assertScript(<<<'JS'
        (() => {
            const regions = Array.from(document.querySelectorAll('[data-release-two-scroll-region]'))
                .filter((region) => region.offsetParent !== null);

            return regions.length === 5
                && regions.every((region) =>
                    region.getAttribute('role') === 'region'
                    && region.tabIndex === 0
                    && (region.getAttribute('aria-label')?.trim().length ?? 0) > 0
                    && region.classList.contains('focus-visible:ring-2')
                );
        })()
    JS);

    $page->script("() => document.querySelector('[data-release-two-scroll-region]').focus()");

    $page->assertScript(<<<'JS'
        (() => {
            const region = document.querySelector('[data-release-two-scroll-region]');

            return region === document.activeElement && region.matches(':focus');
        })()
    JS)
        ->assertNoAccessibilityIssues(1)
        ->assertNoJavaScriptErrors()
        ->assertNoBrokenImages();
});

it('keeps calculation actions and the document inside 360 pixels', function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $this->actingAs($scenario->admin);

    visit(route('admin.calculations'))
        ->resize(360, 800)
        ->waitForText('Kalkulasi UEQ dan SAW')
        ->assertVisible('[data-flux-sidebar-toggle]')
        ->press('Jalankan preview')
        ->waitForText('Input hash')
        ->assertSee('Pooled reliability')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertScript(<<<'JS'
            (() => {
                const actions = document.querySelector('[data-release-two-actions]');
                const viewportWidth = window.innerWidth;

                return actions !== null
                    && Array.from(actions.querySelectorAll('button')).every((button) => {
                        const rect = button.getBoundingClientRect();

                        return rect.left >= 0 && rect.right <= viewportWidth;
                    });
            })()
        JS)
        ->assertNoJavaScriptErrors()
        ->assertNoBrokenImages();
});
