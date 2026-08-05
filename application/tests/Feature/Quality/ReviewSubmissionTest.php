<?php

use App\Application\Quality\ReviewSubmission;
use App\Domain\Quality\QualityDecision;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('records an excluded response with its reviewer and audit event', function () {
    $fixture = completedSubmissionFixture();
    $reviewer = User::factory()->create();

    $review = app(ReviewSubmission::class)->handle(
        $fixture->submission,
        $reviewer,
        QualityDecision::Excluded,
        'Durasi respons tidak masuk akal untuk 26 item.',
    );

    expect($review->decision)->toBe(QualityDecision::Excluded)
        ->and($review->reason)->toBe('Durasi respons tidak masuk akal untuk 26 item.')
        ->and($review->reviewed_by)->toBe($reviewer->id);

    $this->assertDatabaseHas('audit_events', [
        'action' => 'quality_review.updated',
        'actor_id' => $reviewer->id,
    ]);
});

it('rejects an excluded response without a reason', function () {
    $fixture = completedSubmissionFixture();
    $reviewer = User::factory()->create();

    expect(fn () => app(ReviewSubmission::class)->handle(
        $fixture->submission,
        $reviewer,
        QualityDecision::Excluded,
        '   ',
    ))->toThrow(ValidationException::class);
});

it('records the prior decision when a review changes', function () {
    $fixture = completedSubmissionFixture();
    $reviewer = User::factory()->create();
    $reviews = app(ReviewSubmission::class);

    $reviews->handle($fixture->submission, $reviewer, QualityDecision::Included, null);
    $reviews->handle($fixture->submission, $reviewer, QualityDecision::Excluded, 'Pola jawaban tidak layak.');

    $audit = AuditEvent::query()->latest('id')->firstOrFail();

    expect($audit->old_values['decision'])->toBe('included')
        ->and($audit->new_values['decision'])->toBe('excluded');
});
