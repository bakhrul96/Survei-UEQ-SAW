<?php

namespace App\Application\Reporting;

use App\Models\EvaluationPeriod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class AggregateReportExport
{
    public function __construct(
        private readonly AggregateReportQuery $query = new AggregateReportQuery,
    ) {}

    public function spreadsheet(EvaluationPeriod $period): Spreadsheet
    {
        $data = $this->query->for($period);
        $spreadsheet = new Spreadsheet;

        // Sheet 1: Metadata Run
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Metadata Run');
        $sheet1->fromArray([
            ['Parameter', 'Nilai'],
            ['Nama Periode', $period->name],
            ['Slug', $period->slug],
            ['Versi Instrumen', $period->instrument_version],
            ['Run ID', $data->latestRun->id ?? '-'],
            ['Status Run', $data->latestRun->status ?? '-'],
            ['Input Hash', $data->latestRun->input_hash ?? '-'],
            ['Jumlah Response Included', $data->latestRun->included_count ?? 0],
            ['Jumlah Response Excluded', $data->latestRun->excluded_count ?? 0],
            ['Tanggal Ditentukan', $data->latestRun?->calculated_at?->toIso8601String() ?? '-'],
            ['Dikunci Oleh', $data->latestRun?->lockedBy->name ?? '-'],
            ['Waktu Kunci Official', $data->latestRun?->official_locked_at?->toIso8601String() ?? '-'],
        ]);

        // Sheet 2: Hasil UEQ
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Hasil UEQ');
        $ueqRows = [
            ['Unit Code', 'Unit Name', 'Scale', 'n', 'Mean', 'SD', 'Cronbach Alpha', 'Gap'],
        ];
        if ($data->latestRun) {
            foreach ($data->latestRun->ueqResults as $result) {
                $ueqRows[] = [
                    $result->unit->code ?? '',
                    $result->unit->name ?? '',
                    $result->scale,
                    $result->n,
                    $result->mean,
                    $result->standard_deviation,
                    $result->cronbach_alpha,
                    $result->gap,
                ];
            }
        }
        $sheet2->fromArray($ueqRows);

        // Sheet 3: Peringkat SAW
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Peringkat SAW');
        $sawRows = [
            ['Rank', 'Unit Code', 'Unit Name', 'X1 Gap', 'X2 Days', 'X3 Urgency', 'R1', 'R2', 'R3', 'Contrib C1', 'Contrib C2', 'Contrib C3', 'Preference Vi', 'Is Tied'],
        ];
        if ($data->latestRun) {
            foreach ($data->latestRun->sawResults->sortBy('rank') as $result) {
                $sawRows[] = [
                    $result->rank,
                    $result->unit->code ?? '',
                    $result->unit->name ?? '',
                    $result->x1_gap,
                    $result->x2_days,
                    $result->x3_urgency,
                    $result->r1,
                    $result->r2,
                    $result->r3,
                    $result->contribution_c1,
                    $result->contribution_c2,
                    $result->contribution_c3,
                    $result->preference_value,
                    $result->is_tied ? 'YA' : 'TIDAK',
                ];
            }
        }
        $sheet3->fromArray($sawRows);

        // Sheet 4: Analisis Sensitivitas
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Analisis Sensitivitas');
        $sensRows = [
            ['Skenario', 'Unit Code', 'Unit Name', 'Nilai Preferensi', 'Rank', 'Delta Rank', 'Is Tied'],
        ];
        if ($data->latestRun) {
            foreach ($data->latestRun->sensitivityResults as $result) {
                $sensRows[] = [
                    $result->scenario->value,
                    $result->evaluationUnit->code ?? '',
                    $result->evaluationUnit->name ?? '',
                    $result->preference_value,
                    $result->rank,
                    $result->delta_rank,
                    $result->is_tied ? 'YA' : 'TIDAK',
                ];
            }
        }
        $sheet4->fromArray($sensRows);

        // Sheet 5: Backlog Operasional
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Backlog Operasional');
        $ejRows = [
            ['Urutan Backlog', 'Unit Code', 'Unit Name', 'Keputusan', 'Alasan Expert Judgment', 'Reviewer', 'Waktu Ditinjau'],
        ];
        if ($data->latestRun) {
            foreach ($data->latestRun->expertJudgments->sortBy('operational_order') as $ej) {
                $ejRows[] = [
                    $ej->operational_order,
                    $ej->evaluationUnit->code ?? '',
                    $ej->evaluationUnit->name ?? '',
                    $ej->decision,
                    $ej->reason,
                    $ej->reviewer->name ?? '',
                    $ej->updated_at?->toIso8601String() ?? '',
                ];
            }
        }
        $sheet5->fromArray($ejRows);

        return $spreadsheet;
    }
}
