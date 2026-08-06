<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\GoldenFixture;

it('keeps every machine-readable golden value equal to the independent workbook', function (): void {
    $json = GoldenFixture::data();
    $book = IOFactory::load(dirname(__DIR__, 3).'/../docs/research/ueq-saw-golden-fixture.xlsx');
    $overview = $book->getSheetByName('Overview');
    $technical = $book->getSheetByName('Technical and SAW');

    expect($json['algorithm_version'])->toBe('ueq-saw-v1');

    $overviewColumns = [
        'n' => 'C',
        'mean' => 'D',
        'standard_deviation' => 'E',
        'standard_error' => 'F',
        'ci95_lower' => 'G',
        'ci95_upper' => 'H',
        'alpha' => 'I',
    ];
    $row = 14;
    foreach ($json['expected']['ueq'] as $unit => $scales) {
        foreach ($scales as $scale => $expected) {
            expect($overview->getCell("A{$row}")->getValue())->toBe($unit)
                ->and($overview->getCell("B{$row}")->getValue())->toBe($scale);
            foreach ($overviewColumns as $key => $column) {
                expect((float) $overview->getCell("{$column}{$row}")->getCalculatedValue())
                    ->toEqualWithDelta((float) $expected[$key], $json['tolerance']);
            }
            expect((float) $overview->getCell("J{$row}")->getCalculatedValue())
                ->toEqualWithDelta($json['expected']['gaps'][$unit][$scale], $json['tolerance']);
            $row++;
        }
    }

    foreach (array_values($json['benchmarks']) as $index => $threshold) {
        expect((float) $overview->getCell('B'.($index + 4))->getCalculatedValue())
            ->toEqualWithDelta($threshold, $json['tolerance']);
    }

    foreach (['c1' => 'B12', 'c2' => 'C12', 'c3' => 'D12'] as $criterion => $cell) {
        expect((float) $technical->getCell($cell)->getCalculatedValue())
            ->toEqualWithDelta($json['expected']['saw']['weights'][$criterion], $json['tolerance']);
    }

    $sawColumns = [
        'x1_gap' => 'B', 'x2_days' => 'C', 'x3_urgency' => 'D',
        'r1' => 'E', 'r2' => 'F', 'r3' => 'G',
        'contribution_c1' => 'H', 'contribution_c2' => 'I', 'contribution_c3' => 'J', 'vi' => 'K',
    ];
    foreach (['ibadah-yu' => 15, 'info-yu' => 16] as $unit => $sawRow) {
        foreach ($sawColumns as $key => $column) {
            expect((float) $technical->getCell("{$column}{$sawRow}")->getCalculatedValue())
                ->toEqualWithDelta($json['expected']['saw'][$unit][$key], $json['tolerance']);
        }
        $tieCell = $unit === 'ibadah-yu' ? 'B19' : 'B20';
        expect($technical->getCell($tieCell)->getCalculatedValue())->toBe('1 (tied)')
            ->and($json['expected']['saw'][$unit]['rank'])->toBe(1)
            ->and($json['expected']['saw'][$unit]['is_tied'])->toBeTrue();

        $sensitivityColumns = [
            'S0' => ['vi' => 'L', 'rank' => 'M', 'delta' => null],
            'S1' => ['vi' => 'N', 'rank' => 'O', 'delta' => 'R'],
            'S2' => ['vi' => 'P', 'rank' => 'Q', 'delta' => 'S'],
        ];
        foreach ($sensitivityColumns as $scenario => $columns) {
            expect((float) $technical->getCell("{$columns['vi']}{$sawRow}")->getCalculatedValue())
                ->toEqualWithDelta($json['expected']['sensitivity'][$scenario][$unit]['vi'], $json['tolerance'])
                ->and((int) $technical->getCell("{$columns['rank']}{$sawRow}")->getCalculatedValue())
                ->toBe($json['expected']['sensitivity'][$scenario][$unit]['rank']);
            if ($columns['delta'] !== null) {
                expect((int) $technical->getCell("{$columns['delta']}{$sawRow}")->getCalculatedValue())
                    ->toBe($json['expected']['sensitivity'][$scenario][$unit]['delta_rank']);
            }
        }
    }
});

it('retains formulas across every calculation stage in the workbook', function (): void {
    $book = IOFactory::load(dirname(__DIR__, 3).'/../docs/research/ueq-saw-golden-fixture.xlsx');
    $formulaRanges = [
        ['Transformed Scores', 'D4:AC13'],
        ['Respondent Scale Means', 'C4:J15'],
        ['Overview', 'C14:J25'],
        ['Technical and SAW', 'H4:H9'],
        ['Technical and SAW', 'B12:D12'],
        ['Technical and SAW', 'B15:K16'],
        ['Technical and SAW', 'L15:S16'],
        ['Technical and SAW', 'B19:B20'],
    ];

    foreach ($formulaRanges as [$sheetName, $range]) {
        $sheet = $book->getSheetByName($sheetName);
        foreach ($sheet->rangeToArray($range, null, false, false, true) as $cells) {
            foreach ($cells as $coordinate => $value) {
                expect($value, "{$sheetName}!{$coordinate} harus berupa formula")
                    ->toBeString()
                    ->toStartWith('=');
            }
        }
    }
});
