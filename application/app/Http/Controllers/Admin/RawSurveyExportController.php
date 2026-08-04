<?php

namespace App\Http\Controllers\Admin;

use App\Application\Reporting\RawSurveyExport;
use App\Http\Controllers\Controller;
use App\Models\EvaluationPeriod;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RawSurveyExportController extends Controller
{
    public function csv(EvaluationPeriod $period, RawSurveyExport $export): StreamedResponse
    {
        return $this->download($period, $export, 'csv');
    }

    public function xlsx(EvaluationPeriod $period, RawSurveyExport $export): StreamedResponse
    {
        return $this->download($period, $export, 'xlsx');
    }

    private function download(EvaluationPeriod $period, RawSurveyExport $export, string $format): StreamedResponse
    {
        $extension = $format === 'csv' ? 'csv' : 'xlsx';

        return response()->streamDownload(function () use ($period, $export, $format): void {
            $writer = $format === 'csv' ? new Csv($export->spreadsheet($period)) : new Xlsx($export->spreadsheet($period));
            if ($writer instanceof Csv) {
                $writer->setUseBOM(true);
                $writer->setDelimiter(',');
                $writer->setEnclosure('"');
            }
            $writer->save('php://output');
        }, "raw-ueq-{$period->slug}.{$extension}");
    }
}
