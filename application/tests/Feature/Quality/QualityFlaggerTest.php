<?php

use App\Domain\Quality\QualityFlagger;

it('flags a response completed below the period threshold with identical answers', function () {
    $fixture = completedSubmissionFixture();
    $fixture->submission->update(['duration_seconds' => 119]);

    expect(app(QualityFlagger::class)->for($fixture->submission->fresh()))
        ->toBe([
            'fast_completion' => true,
            'identical_answers' => true,
        ]);
});

it('respects the locked identical-answer flag setting', function () {
    $fixture = completedSubmissionFixture();
    $submission = $fixture->submission->fresh();
    $period = $submission->period;
    $period->forceFill(['identical_answers_flag_enabled' => false]);
    $submission->setRelation('period', $period);

    expect(app(QualityFlagger::class)->for($submission)['identical_answers'])->toBeFalse();
});
