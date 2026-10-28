<?php

namespace App\Http\Controllers;

use App\Data\MonitoringFilter;
use App\Models\DokumenPendukung;
use App\Services\{ReportService, ExportService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Gate, Storage};

class BosDownloadController extends Controller
{
    public function template(Request $request, string $module)
    {
        abort_unless($request->user()->can('imports.run') && isset(\App\Services\ImportService::modules()[$module]), 403);
        return response()->streamDownload(function () use ($module) {
            $writer = new \OpenSpout\Writer\XLSX\Writer(); $writer->openToFile('php://output');
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(array_keys(config('bos-modules.'.$module.'.fields')))); $writer->close();
        }, 'template-'.$module.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
    public function document(DokumenPendukung $document)
    {
        abort_unless($document->target, 404); Gate::authorize('download', $document->target);
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        return Storage::disk('local')->download($document->path, $document->nama, ['X-Content-Type-Options' => 'nosniff']);
    }
    public function report(Request $request, string $format)
    {
        abort_unless($request->user()->can('reports.export'), 403);
        return app(ExportService::class)->download(app(ReportService::class)->dataset($request->string('jenis')->toString(), new MonitoringFilter($request->input('filter', []))), $format);
    }
    public function module(Request $request, string $module, string $format)
    {
        abort_unless($request->user()->can('reports.export'), 403);
        return app(ExportService::class)->download(app(ReportService::class)->module($module, $request->input('table_filters', [])), $format);
    }
}
