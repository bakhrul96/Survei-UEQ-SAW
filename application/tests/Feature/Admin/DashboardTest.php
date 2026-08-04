<?php

use App\Application\Reporting\ReleaseOneDashboardQuery;

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
