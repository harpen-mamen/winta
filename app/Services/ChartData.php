<?php

namespace App\Services;

use App\Data\MonitoringFilter;
use App\Models\{PaguBos, PenyaluranBos, PenerimaanBos, RealisasiBos, TahapPenyaluran};

class ChartData
{
    public function get(string $key, MonitoringFilter $filter): array
    {
        $service = app(MonitoringService::class); $labels = []; $sets = [];
        $colors = ['#0d9488', '#3b82f6', '#f59e0b', '#8b5cf6', '#f43f5e', '#64748b'];
        if (! empty($filter->values['tahap_penyaluran_id']) && in_array($key, ['rkas', 'komponen', 'realisasi-trend'])) return ['labels' => [], 'datasets' => []];
        if (in_array($key, ['pagu', 'rkas', 'kecamatan'])) {
            $rows = $service->rows($filter);
            if ($key === 'kecamatan') {
                $grouped = [];
                foreach ($rows as $row) {
                    foreach (['pagu', 'penerimaan'] as $field) $grouped[$row['kecamatan']][$field] = Money::add($grouped[$row['kecamatan']][$field] ?? '0', $row[$field]);
                }
                $labels = array_keys($grouped); $rows = array_values($grouped);
            } else $labels = array_map(fn ($row) => $row['sekolah'].' · '.$row['tahun'].' · '.$row['jenis'], $rows);
            foreach ($key === 'rkas' ? ['anggaran_rkas' => 'Anggaran RKAS', 'realisasi' => 'Realisasi'] : ['pagu' => 'Pagu Berlaku', 'penerimaan' => 'Penerimaan'] as $field => $label) $sets[] = ['label' => $label, 'data' => array_map(fn ($row) => (float) $row[$field], $rows)];
        } elseif ($key === 'tahap') {
            $stages = TahapPenyaluran::query()->when($filter->values['tahun_anggaran_id'] ?? null, fn ($q, $id) => $q->where('tahun_anggaran_id', $id))->when($filter->values['jenis_bos_id'] ?? null, fn ($q, $id) => $q->where('jenis_bos_id', $id))->when($filter->values['tahap_penyaluran_id'] ?? null, fn ($q, $id) => $q->whereKey($id))->orderBy('urutan')->get();
            if (in_array($filter->values['lingkup'] ?? null, ['demo', 'resmi'])) {
                $ids = $service->query(PenyaluranBos::class, $filter)->pluck('tahap_penyaluran_id');
                $stages = $stages->whereIn('id', $ids);
            }
            $labels = $stages->map(fn ($r) => $r->label)->all();
            foreach ([PenyaluranBos::class => 'Penyaluran', PenerimaanBos::class => 'Penerimaan'] as $model => $label) {
                $data = [];
                foreach ($stages as $stage) $data[] = (float) $service->sum($model, new MonitoringFilter(array_merge($filter->values, ['tahap_penyaluran_id' => $stage->id])));
                $sets[] = ['label' => $label, 'data' => $data];
            }
        } elseif (str_contains($key, 'trend')) {
            $model = $key === 'penerimaan-trend' ? PenerimaanBos::class : RealisasiBos::class;
            $groups = $service->query($model, $filter)->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan, SUM(nominal) as total")->groupByRaw("DATE_FORMAT(tanggal, '%Y-%m')")->orderBy('bulan')->get();
            $labels = $groups->pluck('bulan')->all(); $sets[] = ['label' => 'Nominal (Rp)', 'data' => $groups->map(fn ($r) => (float) $r->total)->all()];
        } elseif ($key === 'komponen') {
            $groups = $service->query(RealisasiBos::class, $filter)->with('rincian.komponen')->get()->groupBy(fn ($r) => $r->rincian->komponen->nama);
            $labels = $groups->keys()->all(); $sets[] = ['label' => 'Realisasi', 'data' => $groups->map(fn ($records) => (float) $records->reduce(fn ($sum, $r) => Money::add($sum, $r->nominal), '0'))->values()->all()];
        } else {
            $groups = $service->query(PaguBos::class, $filter, false, auth()->user()?->hasRole('Pimpinan Dinas') ?? true)->selectRaw('status, COUNT(*) as total')->groupBy('status')->get();
            $labels = $groups->map(fn ($r) => ModuleFields::statuses()[$r->status] ?? $r->status)->all(); $sets[] = ['label' => 'Pagu per status', 'data' => $groups->pluck('total')->all()];
        }
        foreach ($sets as $i => &$set) {
            $set['backgroundColor'] = in_array($key, ['komponen', 'verifikasi']) ? $colors : $colors[$i % count($colors)];
            $set['borderColor'] = $colors[$i % count($colors)]; $set['borderRadius'] = 5;
            $set['maxBarThickness'] = 30; $set['tension'] = 0.35; $set['pointRadius'] = 3;
            if (in_array($key, ['komponen', 'verifikasi'])) { $set['borderWidth'] = 3; $set['borderColor'] = '#ffffff'; }
        }
        return ['labels' => $labels, 'datasets' => $labels ? $sets : []];
    }
}
