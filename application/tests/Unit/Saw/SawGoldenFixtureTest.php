<?php

use Tests\Support\GoldenFixture;

it('matches every golden saw intermediate and final value', function (): void {
    $fixture = GoldenFixture::data();
    $rows = GoldenFixture::sawRows();

    expect($rows)->toHaveCount(2);
    foreach ($rows as $row) {
        $expected = $fixture['expected']['saw'][$row->alternative->unitCode];
        expect($row->alternative->gap)->toEqualWithDelta($expected['x1_gap'], $fixture['tolerance'])
            ->and($row->alternative->meanDays)->toEqualWithDelta($expected['x2_days'], $fixture['tolerance'])
            ->and($row->alternative->meanUrgency)->toEqualWithDelta($expected['x3_urgency'], $fixture['tolerance'])
            ->and($row->r1)->toEqualWithDelta($expected['r1'], $fixture['tolerance'])
            ->and($row->r2)->toEqualWithDelta($expected['r2'], $fixture['tolerance'])
            ->and($row->r3)->toEqualWithDelta($expected['r3'], $fixture['tolerance'])
            ->and($row->contributionC1)->toEqualWithDelta($expected['contribution_c1'], $fixture['tolerance'])
            ->and($row->contributionC2)->toEqualWithDelta($expected['contribution_c2'], $fixture['tolerance'])
            ->and($row->contributionC3)->toEqualWithDelta($expected['contribution_c3'], $fixture['tolerance'])
            ->and($row->preferenceValue)->toEqualWithDelta($expected['vi'], $fixture['tolerance'])
            ->and($row->rank)->toBe($expected['rank'])
            ->and($row->isTied)->toBe($expected['is_tied']);
    }
});
