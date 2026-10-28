<?php

namespace App\Services;

use App\Data\MonitoringFilter;
use App\Models\{PaguBos, PenyaluranBos, PenerimaanBos, RealisasiBos, SaldoAwalBos, PenyesuaianSaldo, SaldoDilaporkan, Rkas, Sekolah};
use Illuminate\Database\Eloquent\Builder;

class MonitoringService
{
    public function query(string $model, MonitoringFilter $filter, bool $cumulative = false, bool $verified = true): Builder
    {
        $query = $model::query();
        if ($model === PaguBos::class) {
            $filter->pagu($query);
            if (! empty($filter->values['sampai'])) $query->whereDate('tanggal', '<=', $filter->values['sampai']);
        }
        else $query->whereHas('pagu', fn ($q) => $filter->pagu($q)->where('status', 'terverifikasi'));
        if ($verified) $query->where('status', 'terverifikasi');
        if ($model !== PaguBos::class) $filter->dates($query, 'tanggal', $cumulative);
        if (! empty($filter->values['tahap_penyaluran_id'])) {
            $stage = $filter->values['tahap_penyaluran_id'];
            if ($model === PenyaluranBos::class) $query->where('tahap_penyaluran_id', $stage);
            elseif ($model === PenerimaanBos::class) $query->whereHas('penyaluran', fn ($q) => $q->where('tahap_penyaluran_id', $stage));
        }
        return $query;
    }
    public function sum(string $model, MonitoringFilter $filter, string $column = 'nominal', bool $cumulative = false): string
    {
        if ($model === PaguBos::class && $column === 'pagu_berlaku' && ! empty($filter->values['sampai'])) {
            return $this->query($model, $filter)->get()->reduce(function ($sum, $pagu) use ($filter) {
                $revision = $pagu->perubahans()->where('status', 'terverifikasi')->whereDate('tanggal', '<=', $filter->values['sampai'])->orderByDesc('tanggal')->orderByDesc('id')->first();
                return Money::add($sum, $revision?->nilai_baru ?? $pagu->pagu_awal);
            }, '0.00');
        }
        return (string) $this->query($model, $filter, $cumulative)->sum($column);
    }
    public function summary(MonitoringFilter $filter): array
    {
        $pagu = $this->sum(PaguBos::class, $filter, 'pagu_berlaku');
        $received = $this->sum(PenerimaanBos::class, $filter); $spent = $this->sum(RealisasiBos::class, $filter);
        $sent = $this->sum(PenyaluranBos::class, $filter); $rkas = $this->budget($filter);
        $schools = $filter->schools(Sekolah::query())->where('aktif', true);
        foreach (['kecamatan_id' => 'kecamatan_id', 'sekolah_id' => 'id'] as $key => $column) if (! empty($filter->values[$key])) $schools->where($column, $filter->values[$key]);
        $stageSelected = ! empty($filter->values['tahap_penyaluran_id']);
        $receivedToDate = $this->sum(PenerimaanBos::class, $filter, 'nominal', true);
        $balance = $stageSelected ? null : Money::sub(Money::add($this->sum(SaldoAwalBos::class, $filter, 'nominal', true), $receivedToDate, $this->sum(PenyesuaianSaldo::class, $filter, 'nominal', true)), $this->sum(RealisasiBos::class, $filter, 'nominal', true));
        return ['jumlah_sekolah' => $schools->count(), 'pagu' => $pagu, 'penyaluran' => $sent, 'penerimaan' => $received,
            'realisasi' => $stageSelected ? null : $spent, 'belum_diterima' => Money::sub($pagu, $receivedToDate), 'saldo' => $balance,
            'persen_penerimaan' => Money::percent($receivedToDate, $pagu), 'persen_realisasi' => $stageSelected ? null : Money::percent($spent, $rkas),
            'anggaran_rkas' => $stageSelected ? null : $rkas, 'selisih' => Money::sub($sent, $received)];
    }
    public function budget(MonitoringFilter $filter): string
    {
        $sum = '0.00';
        foreach ($this->query(PaguBos::class, $filter)->get() as $pagu) {
            $version = Rkas::where('pagu_bos_id', $pagu->id)->where('status', 'terverifikasi')
                ->when($filter->values['sampai'] ?? null, fn ($q, $date) => $q->whereDate('tanggal', '<=', $date))->orderByDesc('versi')->first();
            if ($version) $sum = Money::add($sum, (string) $version->details()->sum('nominal'));
        }
        return $sum;
    }
    public function rows(MonitoringFilter $filter): array
    {
        $rows = [];
        foreach ($this->query(PaguBos::class, $filter)->with(['sekolah.kecamatan', 'tahunAnggaran', 'jenisBos'])->get() as $pagu) {
            $context = new MonitoringFilter(array_merge($filter->values, ['sekolah_id' => $pagu->sekolah_id, 'tahun_anggaran_id' => $pagu->tahun_anggaran_id, 'jenis_bos_id' => $pagu->jenis_bos_id]));
            $rows[] = ['id' => $pagu->id, 'npsn' => $pagu->sekolah->npsn, 'sekolah' => $pagu->sekolah->nama, 'kecamatan' => $pagu->sekolah->kecamatan->nama,
                'tahun' => $pagu->tahunAnggaran->tahun, 'jenis' => $pagu->jenisBos->nama] + $this->summary($context);
        }
        return $rows;
    }
    public function findings(MonitoringFilter $filter): array
    {
        $findings = [];
        $add = function ($school, $indicator, $message, $paguId = null) use (&$findings) { $findings[] = ['sekolah' => $school, 'indikator' => $indicator, 'catatan' => $message, 'pagu_id' => $paguId]; };
        $schools = $filter->schools(Sekolah::query())->where('aktif', true)->when($filter->values['kecamatan_id'] ?? null, fn ($q, $id) => $q->where('kecamatan_id', $id))->when($filter->values['sekolah_id'] ?? null, fn ($q, $id) => $q->whereKey($id))->get();
        foreach ($schools as $school) if (! $this->query(PaguBos::class, $filter)->where('sekolah_id', $school->id)->exists()) $add($school->nama, 'Perlu Perhatian', 'Belum ada pagu terverifikasi pada konteks filter ini.');
        foreach ($this->rows($filter) as $row) {
            if (bccomp($row['selisih'], '0', 2) !== 0) $add($row['sekolah'], 'Terdapat Selisih', 'Selisih penyaluran dan penerimaan: '.Money::idr($row['selisih']), $row['id']);
            if ($row['saldo'] !== null) {
                $reported = SaldoDilaporkan::where('pagu_bos_id', $row['id'])->where('status', 'terverifikasi')->when($filter->values['sampai'] ?? null, fn ($q, $d) => $q->whereDate('tanggal', '<=', $d))->orderByDesc('tanggal')->first();
                if ($reported) {
                    $pagu = PaguBos::find($row['id']);
                    $atReport = new MonitoringFilter(array_merge($filter->values, ['mulai' => null, 'sekolah_id' => $pagu->sekolah_id, 'tahun_anggaran_id' => $pagu->tahun_anggaran_id, 'jenis_bos_id' => $pagu->jenis_bos_id, 'sampai' => $reported->tanggal->format('Y-m-d')]));
                    if (bccomp($this->summary($atReport)['saldo'], $reported->nominal, 2) !== 0) $add($row['sekolah'], 'Terdapat Selisih', 'Saldo dilaporkan berbeda dari saldo terhitung pada tanggal laporan.', $row['id']);
                }
            }
        }
        if (! auth()->user()?->hasRole('Pimpinan Dinas')) {
            foreach (config('bos-modules') as $module) if ($module['finance']) {
                foreach ($this->query($module['model'], $filter, false, false)->with('documents')->get() as $record) {
                    $school = $record instanceof PaguBos ? $record->sekolah?->nama : $record->pagu?->sekolah?->nama;
                    if ($record->status !== 'terverifikasi') $add($school, 'Perlu Verifikasi', $module['label'].' #'.$record->id.' belum terverifikasi.', $record->pagu_bos_id ?? $record->id);
                    if ($record->documents->isEmpty()) $add($school, 'Perlu Perhatian', $module['label'].' #'.$record->id.' belum memiliki dokumen.', $record->pagu_bos_id ?? $record->id);
                }
            }
        }
        foreach ($this->query(RealisasiBos::class, $filter)->with('rincian')->get()->groupBy('rkas_detail_id') as $records) {
            $spent = $records->reduce(fn ($sum, $r) => Money::add($sum, $r->nominal), '0.00');
            if (bccomp($spent, $records->first()->rincian->nominal, 2) > 0) $add($records->first()->pagu->sekolah->nama, 'Perlu Perhatian', 'Realisasi melebihi anggaran komponen RKAS.', $records->first()->pagu_bos_id);
        }
        foreach ([PenyaluranBos::class, PenerimaanBos::class, RealisasiBos::class] as $model) {
            $groups = $this->query($model, $filter, false, auth()->user()?->hasRole('Pimpinan Dinas') ?? true)->whereNull('koreksi_atas_id')->selectRaw('pagu_bos_id, tanggal, nominal, COUNT(*) as total')->groupBy('pagu_bos_id', 'tanggal', 'nominal')->havingRaw('COUNT(*) > 1')->get();
            foreach ($groups as $group) $add($group->pagu->sekolah->nama, 'Perlu Verifikasi', 'Dugaan transaksi ganda: tanggal dan nominal sama pada '.class_basename($model).'. Periksa referensi; ini bukan kesimpulan duplikasi.', $group->pagu_bos_id);
        }
        return $findings;
    }
}
