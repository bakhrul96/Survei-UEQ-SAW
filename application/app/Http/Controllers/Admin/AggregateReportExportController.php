<?php

namespace App\Http\Controllers\Admin;

use App\Application\Reporting\AggregateReportExport;
use App\Http\Controllers\Controller;
use App\Models\EvaluationPeriod;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AggregateReportExportController extends Controller
{
    public function __construct(
        private readonly AggregateReportExport $export = new AggregateReportExport,
    ) {}

    public function xlsx(EvaluationPeriod $period): StreamedResponse
    {
        $spreadsheet = $this->export->spreadsheet($period);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "laporan-agregat-{$period->slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function csv(EvaluationPeriod $period): StreamedResponse
    {
        $spreadsheet = $this->export->spreadsheet($period);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Csv($spreadsheet);
            $writer->setUseBOM(true);
            $writer->setDelimiter(',');
            $writer->save('php://output');
        }, "laporan-agregat-{$period->slug}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
