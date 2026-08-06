<?php

use App\Application\Reporting\ReleaseOneDashboardQuery;
use App\Domain\Study\PeriodStatus;
use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\RespondentProfile;
use App\Models\SurveySession;
use App\Models\SurveySubmission;

it('separates unique respondents from module evaluations', function () {
    $fixture = dashboardFixture(uniqueRespondents: 2, submissions: [
        'ibadah-yu' => 2,
        'info-yu' => 1,
    ]);

    $data = app(ReleaseOneDashboardQuery::class)->for($fixture->period);

    expect($data->uniqueRespondents)->toBe(2)
        ->and($data->totalEvaluations)->toBe(3)
        ->and($data->units->firstWhere('code', 'ibadah-yu')->valid)->toBe(2)
        ->and($data->units->firstWhere('code', 'info-yu')->valid)->toBe(1);
});

it('requires authentication for the dashboard', function () {
    $this->get('/admin/dashboard')->assertRedirect('/login');
});

it('correctly calculates unit progress status (below_minimum, minimal_reached, target_reached)', function () {
    $period = EvaluationPeriod::factory()->create([
        'minimum_per_unit' => 20,
        'target_per_unit' => 30,
        'status' => PeriodStatus::Active,
    ]);

    $unitBelow = EvaluationUnit::factory()->create(['code' => 'unit-below', 'display_order' => 1001]);
    $unitMinimal = EvaluationUnit::factory()->create(['code' => 'unit-minimal', 'display_order' => 1002]);
    $unitTarget = EvaluationUnit::factory()->create(['code' => 'unit-target', 'display_order' => 1003]);

    // Create 0 submissions for unitBelow -> below_minimum
    // Create 20 submissions for unitMinimal -> minimal_reached
    // Create 30 submissions for unitTarget -> target_reached

    $session = SurveySession::factory()->create(['evaluation_period_id' => $period->id]);

    for ($i = 0; $i < 20; $i++) {
        SurveySubmission::factory()->create([
            'evaluation_period_id' => $period->id,
            'evaluation_unit_id' => $unitMinimal->id,
            'survey_session_id' => $session->id,
        ]);
    }

    for ($i = 0; $i < 30; $i++) {
        SurveySubmission::factory()->create([
            'evaluation_period_id' => $period->id,
            'evaluation_unit_id' => $unitTarget->id,
            'survey_session_id' => $session->id,
        ]);
    }

    $data = app(ReleaseOneDashboardQuery::class)->for($period);

    $belowData = $data->units->firstWhere('code', 'unit-below');
    $minimalData = $data->units->firstWhere('code', 'unit-minimal');
    $targetData = $data->units->firstWhere('code', 'unit-target');

    expect($belowData->status)->toBe('below_minimum')
        ->and($belowData->valid)->toBe(0)
        ->and($minimalData->status)->toBe('minimal_reached')
        ->and($minimalData->valid)->toBe(20)
        ->and($targetData->status)->toBe('target_reached')
        ->and($targetData->valid)->toBe(30);
});

it('correctly tracks eligible vs total unique respondents', function () {
    $period = EvaluationPeriod::factory()->create([
        'status' => PeriodStatus::Active,
    ]);

    // 2 eligible, 1 ineligible
    $resp1 = AnonymousRespondent::factory()->create();
    $resp2 = AnonymousRespondent::factory()->create();
    $resp3 = AnonymousRespondent::factory()->create();

    RespondentProfile::factory()->create(['evaluation_period_id' => $period->id, 'anonymous_respondent_id' => $resp1->id, 'eligible' => true]);
    RespondentProfile::factory()->create(['evaluation_period_id' => $period->id, 'anonymous_respondent_id' => $resp2->id, 'eligible' => true]);
    RespondentProfile::factory()->create(['evaluation_period_id' => $period->id, 'anonymous_respondent_id' => $resp3->id, 'eligible' => false]);

    $data = app(ReleaseOneDashboardQuery::class)->for($period);

    expect($data->uniqueRespondents)->toBe(3)
        ->and($data->eligibleRespondents)->toBe(2);
});

it('separates flagged, excluded, and pending review counts on the dashboard', function () {
    $fixture = dashboardFixture(uniqueRespondents: 4, submissions: [
        'ibadah-yu' => 4,
    ]);

    $admin = App\Models\User::factory()->create();
    $submissions = App\Models\SurveySubmission::query()
        ->where('evaluation_period_id', $fixture->period->id)
        ->orderBy('id')
        ->get();

    // Satu flagged (identical answers), keputusan masih pending.
    $submissions[0]->qualityReview()->create([
        'flags' => ['fast_completion' => false, 'identical_answers' => true],
        'decision' => null,
        'reason' => null,
        'reviewed_by' => null,
        'reviewed_at' => null,
    ]);
    // Satu flagged dan sudah diputuskan excluded.
    $submissions[1]->qualityReview()->create([
        'flags' => ['fast_completion' => true, 'identical_answers' => false],
        'decision' => App\Domain\Quality\QualityDecision::Excluded,
        'reason' => 'Durasi di bawah ambang.',
        'reviewed_by' => $admin->id,
        'reviewed_at' => now(),
    ]);
    // Satu diputuskan included tanpa flag.
    $submissions[2]->qualityReview()->create([
        'flags' => ['fast_completion' => false, 'identical_answers' => false],
        'decision' => App\Domain\Quality\QualityDecision::Included,
        'reason' => null,
        'reviewed_by' => $admin->id,
        'reviewed_at' => now(),
    ]);
    // Submission keempat tanpa quality review sama sekali.

    $data = app(ReleaseOneDashboardQuery::class)->for($fixture->period);

    expect($data->flaggedEvaluations)->toBe(2)
        ->and($data->excludedEvaluations)->toBe(1)
        ->and($data->pendingReviewEvaluations)->toBe(1);
});
