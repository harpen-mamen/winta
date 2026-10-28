<?php

namespace App\Services;

use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportService
{
    public function download(array $report, string $format)
    {
        abort_unless(auth()->user()?->can('reports.export'), 403);
        \App\Models\AuditLog::create(['aksi' => 'ekspor_'.$format, 'user_id' => auth()->id(), 'sesudah' => ['laporan' => $report['title'], 'filter' => $report['filters'] ?? []]]);
        if ($format === 'pdf') return Pdf::loadView('reports.pdf', ['report' => $report])->setPaper('a4', 'landscape')->setOption('isRemoteEnabled', false)->download('laporan-bos-'.now()->format('Ymd-His').'.pdf');
        abort_unless($format === 'xlsx', 404);
        return response()->streamDownload(function () use ($report) {
            $writer = new Writer(); $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues([$report['title']]));
            $writer->addRow(Row::fromValues(['Dibuat', $report['created_at'], 'Pembuat', $report['creator']]));
            $writer->addRow(Row::fromValues(['Sumber', $report['source']]));
            $writer->addRow(Row::fromValues(['Filter', json_encode($report['filters'] ?? [], JSON_UNESCAPED_UNICODE)]));
            $writer->addRow(Row::fromValues($report['columns']));
            foreach ($report['rows'] as $row) $writer->addRow(Row::fromValues(array_values($row)));
            $writer->close();
        }, 'laporan-bos-'.now()->format('Ymd-His').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
