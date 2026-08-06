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
