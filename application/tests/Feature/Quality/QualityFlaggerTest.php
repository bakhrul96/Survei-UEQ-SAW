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
