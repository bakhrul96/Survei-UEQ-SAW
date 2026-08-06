<?php

namespace App\Application\Reporting;

use App\Models\EvaluationPeriod;
use App\Models\User;
use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

final class AggregateReportExport
{
    public function __construct(
        private readonly AggregateReportQuery $query = new AggregateReportQuery,
    ) {}

    public function spreadsheet(EvaluationPeriod $period, CarbonInterface $generatedAt): Spreadsheet
    {
        $data = $this->query->for($period);
        $run = $data->selectedRun;
        $spreadsheet = new Spreadsheet;
        $runMetadata = $run === null ? [
            'algorithm_version' => '-', 'id' => '-', 'status' => '-', 'input_hash' => '-',
            'included_count' => 0, 'excluded_count' => 0, 'calculated_at' => '-', 'locked_by' => '-',
            'official_locked_at' => '-', 'minimum_deviation_reason' => '-',
            'minimum_deviation_approval_reference' => '-', 'minimum_deviation_approver' => '-',
            'minimum_deviation_approved_at' => '-',
        ] : [
            'algorithm_version' => $run->algorithm_version,
            'id' => $run->id,
            'status' => $run->status,
            'input_hash' => $run->input_hash,
            'included_count' => $run->included_count,
            'excluded_count' => $run->excluded_count,
            'calculated_at' => $run->calculated_at?->toIso8601String() ?? '-',
            'locked_by' => $run->lockedBy instanceof User ? $run->lockedBy->name : '-',
            'official_locked_at' => $run->official_locked_at?->toIso8601String() ?? '-',
            'minimum_deviation_reason' => $run->minimum_deviation_reason ?? '-',
            'minimum_deviation_approval_reference' => $run->minimum_deviation_approval_reference ?? '-',
            'minimum_deviation_approver' => $run->minimumDeviationApprover instanceof User ? $run->minimumDeviationApprover->name : '-',
            'minimum_deviation_approved_at' => $run->minimum_deviation_approved_at?->toIso8601String() ?? '-',
        ];

        $metadata = $spreadsheet->getActiveSheet();
        $metadata->setTitle('Metadata Run');
        $metadata->fromArray([
            ['Parameter', 'Nilai'],
            ['Nama Periode', $period->name],
            ['Slug', $period->slug],
            ['Versi Instrumen', $period->instrument_version],
            ['Versi Algoritma', $runMetadata['algorithm_version']],
            ['Run ID', $runMetadata['id']],
            ['Status Run', $runMetadata['status']],
            ['Input Hash', $runMetadata['input_hash']],
            ['Jumlah Response Included', $runMetadata['included_count']],
            ['Jumlah Response Excluded', $runMetadata['excluded_count']],
            ['Waktu Kalkulasi', $runMetadata['calculated_at']],
            ['Waktu Ekspor', $generatedAt->toIso8601String()],
            ['Dikunci Oleh', $runMetadata['locked_by']],
            ['Waktu Kunci Official', $runMetadata['official_locked_at']],
            ['Alasan Penyimpangan Minimum', $runMetadata['minimum_deviation_reason']],
            ['Referensi Persetujuan Penyimpangan', $runMetadata['minimum_deviation_approval_reference']],
            ['Penyimpangan Disetujui Oleh', $runMetadata['minimum_deviation_approver']],
            ['Waktu Persetujuan Penyimpangan', $runMetadata['minimum_deviation_approved_at']],
        ]);

        $benchmarkSheet = $spreadsheet->createSheet();
        $benchmarkSheet->setTitle('Benchmark');
        $benchmarkRows = [['Version', 'Scale', 'Good Threshold', 'Source', 'Verified At']];
        foreach ($data->benchmarks as $benchmark) {
            if (! is_array($benchmark)) {
                continue;
            }
            $benchmarkRows[] = [
                $benchmark['version'] ?? null,
                $benchmark['scale'] ?? null,
                $benchmark['good_threshold'] ?? null,
                $benchmark['source'] ?? null,
                $benchmark['verified_at'] ?? null,
            ];
        }
        $benchmarkSheet->fromArray($benchmarkRows);

        $ueqSheet = $spreadsheet->createSheet();
        $ueqSheet->setTitle('Hasil UEQ');
        $ueqRows = [['Unit Code', 'Unit Name', 'Scale', 'n', 'Mean', 'SD', 'SE', 'CI 95% Lower', 'CI 95% Upper', 'Cronbach Alpha', 'Gap']];
        foreach ($data->ueqSummary as $unit) {
            if (! is_array($unit) || ! is_array($unit['scales'] ?? null)) {
                continue;
            }
            foreach ($unit['scales'] as $scale => $values) {
                if (! is_array($values)) {
                    continue;
                }
                $ueqRows[] = [
                    $unit['unit_code'] ?? null,
                    $unit['unit_name'] ?? null,
                    $scale,
                    $values['n'] ?? null,
                    $values['mean'] ?? null,
                    $values['standard_deviation'] ?? null,
                    $values['standard_error'] ?? null,
                    $values['ci95_lower'] ?? null,
                    $values['ci95_upper'] ?? null,
                    $values['cronbach_alpha'] ?? null,
                    $values['gap'] ?? null,
                ];
            }
        }
        $ueqSheet->fromArray($ueqRows);

        $sawSheet = $spreadsheet->createSheet();
        $sawSheet->setTitle('Peringkat SAW');
        $sawRows = [['Rank', 'Unit Code', 'Unit Name', 'X1 Gap', 'X2 Days', 'X3 Urgency', 'R1', 'R2', 'R3', 'Contrib C1', 'Contrib C2', 'Contrib C3', 'Preference Vi', 'Is Tied']];
        foreach ($data->sawRanking as $row) {
            if (! is_array($row)) {
                continue;
            }
            $sawRows[] = [
                $row['rank'] ?? null, $row['unit_code'] ?? null, $row['unit_name'] ?? null,
                $row['x1_gap'] ?? null, $row['x2_days'] ?? null, $row['x3_urgency'] ?? null,
                $row['r1'] ?? null, $row['r2'] ?? null, $row['r3'] ?? null,
                $row['contribution_c1'] ?? null, $row['contribution_c2'] ?? null, $row['contribution_c3'] ?? null,
                $row['vi'] ?? null, ($row['is_tied'] ?? false) ? 'YA' : 'TIDAK',
            ];
        }
        $sawSheet->fromArray($sawRows);

        $sensitivitySheet = $spreadsheet->createSheet();
        $sensitivitySheet->setTitle('Analisis Sensitivitas');
        $sensitivityRows = [['Skenario', 'Unit Code', 'Unit Name', 'Nilai Preferensi', 'Rank', 'Delta Rank', 'Is Tied']];
        foreach ($data->sensitivityMatrix as $unit) {
            if (! is_array($unit) || ! is_array($unit['scenarios'] ?? null)) {
                continue;
            }
            foreach ($unit['scenarios'] as $scenario => $values) {
                if (! is_array($values)) {
                    continue;
                }
                $sensitivityRows[] = [
                    $scenario, $unit['unit_code'] ?? null, $unit['unit_name'] ?? null,
                    $values['preference_value'] ?? null, $values['rank'] ?? null,
                    $values['delta_rank'] ?? null, ($values['is_tied'] ?? false) ? 'YA' : 'TIDAK',
                ];
            }
        }
        $sensitivitySheet->fromArray($sensitivityRows);

        $backlogSheet = $spreadsheet->createSheet();
        $backlogSheet->setTitle('Backlog Operasional');
        $backlogRows = [['Urutan Backlog', 'Unit Code', 'Unit Name', 'Keputusan', 'Alasan Expert Judgment', 'Reviewer', 'Waktu Ditinjau']];
        foreach ($data->operationalBacklog as $row) {
            if (! is_array($row)) {
                continue;
            }
            $backlogRows[] = [
                $row['operational_order'] ?? null, $row['unit_code'] ?? null, $row['unit_name'] ?? null,
                $row['decision'] ?? null, $row['reason'] ?? null, $row['reviewer_name'] ?? null, $row['updated_at'] ?? null,
            ];
        }
        $backlogSheet->fromArray($backlogRows);

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheet->freezePane('A2');
            $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        }

        return $spreadsheet;
    }
}
