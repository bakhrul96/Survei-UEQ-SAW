<?php

use App\Livewire\Admin\Responses;
use App\Models\User;
use Livewire\Livewire;

it('requires authentication to open response review', function () {
    $this->get('/admin/responses')->assertRedirect('/login');
});

it('shows prospective flags without treating the response as reviewed', function () {
    completedSubmissionFixture();
    $admin = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Responses::class)
        ->assertSee('Jawaban identik')
        ->assertSee('Belum direview');
});

it('lets an administrator exclude a submitted response with a reason', function () {
    $fixture = completedSubmissionFixture();
    $admin = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(Responses::class)
        ->call('openReview', $fixture->submission->id)
        ->set('decision', 'excluded')
        ->set('reason', 'Durasi respons terlalu singkat untuk evaluasi utuh.')
        ->call('saveReview')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('quality_reviews', [
        'survey_submission_id' => $fixture->submission->id,
        'decision' => 'excluded',
        'reviewed_by' => $admin->id,
    ]);
});
