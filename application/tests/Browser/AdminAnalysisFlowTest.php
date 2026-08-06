<?php

use Tests\Support\ReleaseTwoFixture;

it('reviews quality records three informants and opens a traceable preview', function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $this->actingAs($scenario->admin);

    visit(route('admin.dashboard'))
        ->resize(1280, 800)
        ->assertSee('Dashboard progres')
        ->click('[data-flux-sidebar-item][href$="/admin/responses"]')
        ->waitForText('Review kualitas respons')
        ->assertSee('Included')
        ->click('[data-flux-sidebar-item][href$="/admin/technical-assessments"]')
        ->waitForText('Informan teknis')
        ->assertSee('3 informan')
        ->assertSee('Lengkap')
        ->click('[data-flux-sidebar-item][href$="/admin/calculations"]')
        ->waitForText('Kalkulasi UEQ dan SAW')
        ->press('Jalankan preview')
        ->waitForText('Input hash')
        ->assertSee('Pooled reliability')
        ->assertSee('Kontribusi C1');
});
