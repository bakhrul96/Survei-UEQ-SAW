<?php

use App\Livewire\Admin\Calculations;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Livewire\Livewire;

it('protects the calculation preview page', function (): void {
    $this->get('/admin/calculations')->assertRedirect('/login');
});

it('renders the calculation preview controls without release three actions', function (): void {
    EvaluationPeriod::factory()->create();
    $admin = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Calculations::class)
        ->assertSee('Jalankan preview')
        ->assertDontSee('Tetapkan hasil resmi')
        ->assertDontSee('Sensitivitas');
});
