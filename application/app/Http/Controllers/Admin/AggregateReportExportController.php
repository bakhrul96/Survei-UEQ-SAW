<?php

namespace App\Http\Controllers\Admin;

use App\Application\Reporting\AggregateCsvExport;
use App\Application\Reporting\AggregateReportExport;
use App\Http\Controllers\Controller;
use App\Models\EvaluationPeriod;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AggregateReportExportController extends Controller
{
    public function __construct(
        private readonly AggregateReportExport $export = new AggregateReportExport,
        private readonly AggregateCsvExport $csvExport = new AggregateCsvExport,
    ) {}

    public function xlsx(EvaluationPeriod $period): StreamedResponse
    {
        $spreadsheet = $this->export->spreadsheet($period, now());

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, "laporan-agregat-{$period->slug}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function csv(EvaluationPeriod $period): StreamedResponse
    {
        return response()->streamDownload(function () use ($period): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                throw new RuntimeException('Output CSV tidak dapat dibuka.');
            }
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($this->csvExport->rows($period, now()) as $row) {
                fputcsv($handle, $row, ',', '"', '\\');
            }
            fclose($handle);
        }, "laporan-agregat-{$period->slug}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
