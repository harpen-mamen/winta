<?php

namespace App\Services;

use App\Data\MonitoringFilter;
use App\Models\{PaguBos, PenyaluranBos, PenerimaanBos, RealisasiBos};
use Illuminate\Support\Facades\Gate;

class ReportService
{
    public static function types(): array
    {
        return ['pagu' => 'Rekap Pagu BOS', 'penyaluran' => 'Rekap Penyaluran', 'penerimaan' => 'Rekap Penerimaan', 'realisasi' => 'Rekap Realisasi', 'pagu_pencairan' => 'Perbandingan Pagu dan Pencairan', 'anggaran_realisasi' => 'Anggaran vs Realisasi', 'saldo' => 'Laporan Saldo Dana', 'kecamatan' => 'Monitoring per Kecamatan', 'sekolah' => 'Monitoring per Sekolah', 'tahunan' => 'Laporan Tahunan'];
    }
    public function dataset(string $type, MonitoringFilter $filter): array
    {
        abort_unless(auth()->user()?->can('monitoring.view'), 403);
        abort_unless(isset(static::types()[$type]), 404);
        $service = app(MonitoringService::class); $rows = [];
        if (in_array($type, ['pagu', 'penyaluran', 'penerimaan', 'realisasi'])) {
            $class = ['pagu' => PaguBos::class, 'penyaluran' => PenyaluranBos::class, 'penerimaan' => PenerimaanBos::class, 'realisasi' => RealisasiBos::class][$type];
            abort_unless(auth()->user()->can((new $class)->getTable().'.view'), 403);
            $query = $service->query($class, $filter);
            if ($class === RealisasiBos::class && ! empty($filter->values['tahap_penyaluran_id'])) $query->whereRaw('1 = 0');
            foreach ($query->get() as $record) {
                $pagu = $record instanceof PaguBos ? $record : $record->pagu;
                $amount = $record->nominal;
                $source = $record->sumber;
                $reference = $record->referensi ?? $record->nomor_dokumen;
                if ($record instanceof PaguBos) {
                    $amount = $service->sum(PaguBos::class, new MonitoringFilter(array_merge($filter->values, ['sekolah_id' => $record->sekolah_id, 'tahun_anggaran_id' => $record->tahun_anggaran_id, 'jenis_bos_id' => $record->jenis_bos_id])), 'pagu_berlaku');
                    $revision = $record->perubahans()->where('status', 'terverifikasi')->when($filter->values['sampai'] ?? null, fn ($q, $date) => $q->whereDate('tanggal', '<=', $date))->orderByDesc('tanggal')->orderByDesc('id')->first();
                    if ($revision) { $source = $revision->sumber; $reference .= ' / Perubahan #'.$revision->id; }
                }
                $rows[] = ['NPSN' => $pagu->sekolah->npsn, 'Sekolah' => $pagu->sekolah->nama, 'Tahun' => (string) $pagu->tahunAnggaran->tahun, 'Jenis BOS' => $pagu->jenisBos->nama,
                    'Tanggal' => $record->tanggal->format('Y-m-d'), 'Referensi' => $reference,
                    'Nominal (IDR)' => $amount, 'Sumber' => $source, 'Status' => 'terverifikasi'];
            }
        } else {
            foreach ($service->rows($filter) as $row) {
                $values = match ($type) {
                    'anggaran_realisasi' => ['Anggaran RKAS (IDR)' => $row['anggaran_rkas'], 'Realisasi (IDR)' => $row['realisasi'], 'Persentase Realisasi' => $row['persen_realisasi']],
                    'saldo' => ['Saldo Terhitung (IDR)' => $row['saldo'], 'Sisa Pagu Belum Diterima (IDR)' => $row['belum_diterima']],
                    default => ['Pagu (IDR)' => $row['pagu'], 'Penyaluran (IDR)' => $row['penyaluran'], 'Penerimaan (IDR)' => $row['penerimaan'], 'Realisasi (IDR)' => $row['realisasi'], 'Saldo (IDR)' => $row['saldo'], 'Selisih Penyaluran (IDR)' => $row['selisih']],
                };
                $rows[] = ['NPSN' => $row['npsn'], 'Sekolah' => $row['sekolah'], 'Kecamatan' => $row['kecamatan'], 'Tahun' => (string) $row['tahun'], 'Jenis BOS' => $row['jenis']] + array_map(fn ($v) => $v === null ? 'Tidak tersedia' : (string) $v, $values);
            }
            if ($type === 'kecamatan') {
                $grouped = [];
                foreach ($rows as $row) {
                    $key = $row['Kecamatan'].' / '.$row['Tahun'].' / '.$row['Jenis BOS'];
                    if (! isset($grouped[$key])) $grouped[$key] = ['Kecamatan' => $row['Kecamatan'], 'Tahun' => $row['Tahun'], 'Jenis BOS' => $row['Jenis BOS']];
                    foreach ($row as $column => $value) if (str_contains($column, '(IDR)')) $grouped[$key][$column] = $value === 'Tidak tersedia' ? $value : Money::add($grouped[$key][$column] ?? '0', $value);
                }
                $rows = array_values($grouped);
            }
        }
        return DemoLabel::report(['title' => static::types()[$type], 'columns' => $rows ? array_keys($rows[0]) : ['Keterangan'], 'rows' => $rows, 'created_at' => now()->format('d/m/Y H:i').' WITA', 'creator' => auth()->user()->name, 'source' => 'Database Monitoring BOS SMP Sangihe — hanya data terverifikasi', 'filters' => $filter->values]);
    }
    public function module(string $key, array $filters = []): array
    {
        $module = config('bos-modules.'.$key); abort_unless($module && auth()->user()?->can($key.'.view'), 403);
        $resource = 'App\\Filament\\Resources\\'.class_basename($module['model']).'Resource';
        $query = $resource::getEloquentQuery();
        $scope = $filters['lingkup'] ?? null;
        $resource::applyDataScope($query, is_array($scope) ? ($scope['value'] ?? null) : $scope);
        foreach ($filters as $column => $state) {
            $value = is_array($state) ? ($state['value'] ?? null) : $state;
            if (filled($value) && (isset($module['fields'][$column]) || ($column === 'status' && $module['finance']))) $query->where($column, $value);
        }
        $rows = [];
        foreach ($query->get() as $record) {
            Gate::authorize('view', $record); $row = [];
            foreach ($module['fields'] as $column => $type) $row[ModuleFields::label($column)] = ModuleFields::display($record->$column, $type);
            if ($module['finance']) $row['Status Verifikasi'] = $record->status;
            $rows[] = $row;
        }
        return DemoLabel::report(['title' => $module['label'], 'columns' => $rows ? array_keys($rows[0]) : array_map(ModuleFields::label(...), array_keys($module['fields'])), 'rows' => $rows,
            'created_at' => now()->format('d/m/Y H:i').' WITA', 'creator' => auth()->user()->name, 'source' => 'Database Monitoring BOS SMP Sangihe — status tercantum per baris', 'filters' => $filters]);
    }
}
