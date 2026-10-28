<?php

namespace App\Services;

use App\Models\{BosModel, FinancialRecord, PaguBos, PerubahanPagu, TahunAnggaran, PenyaluranBos, PenerimaanBos, TahapPenyaluran, Rkas, RkasDetail, KomponenBelanja, RealisasiBos};
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecordValidation
{
    public function validate(BosModel $record): void
    {
        $module = config('bos-modules.'.$record->getTable());
        if (! $module || $module['readonly']) return;
        $rules = [];
        foreach ($module['fields'] as $field => $type) {
            $optional = str_ends_with($type, '?'); $type = rtrim($type, '?');
            $rule = [$optional ? 'nullable' : 'required'];
            if ($type === 'bool') { $rule = ['sometimes', 'boolean']; }
            elseif ($type === 'npsn') $rule[] = 'regex:/^[0-9]{8}$/';
            elseif (in_array($type, ['money', 'signed'])) { $rule[] = 'regex:/^-?\d{1,16}(\.\d{1,2})?$/'; if ($type === 'money') $rule[] = 'gte:0'; }
            elseif (in_array($type, ['year', 'integer'])) { $rule[] = 'integer'; $rule[] = $type === 'year' ? 'between:2000,2100' : 'min:0'; }
            elseif ($type === 'date') $rule[] = 'date_format:Y-m-d';
            elseif (str_starts_with($type, 'enum:')) $rule[] = Rule::in(explode(',', substr($type, 5)));
            elseif (str_contains($type, ':')) {
                [$class] = explode(':', $type); $class = 'App\\Models\\'.$class;
                $rule[] = Rule::exists((new $class)->getTable(), 'id');
            } else { $rule[] = 'string'; $rule[] = $type === 'textarea' ? 'max:5000' : 'max:255'; }
            if (in_array($field, ['npsn', 'referensi']) || ($field === 'kode' && in_array($record->getTable(), ['kecamatans', 'jenis_bos'])) || ($field === 'tahun' && $record instanceof TahunAnggaran)) {
                $rule[] = Rule::unique($record->getTable(), $field)->ignore($record->getKey());
            }
            $rules[$field] = $rule;
        }
        $attributes = $record->getAttributes();
        foreach ($rules as $key => $_) if (isset($attributes[$key]) && $record->hasCast($key, 'date')) $attributes[$key] = substr($attributes[$key], 0, 10);
        Validator::make($attributes, $rules)->validate();
        if ($record instanceof FinancialRecord && $record->pagu_bos_id) {
            $pagu = PaguBos::query()->lockForUpdate()->findOrFail($record->pagu_bos_id);
            $this->require($pagu->status === 'terverifikasi', 'pagu_bos_id', 'Pagu harus terverifikasi sebelum input data turunannya.');
            $this->require(! $pagu->tahunAnggaran->dikunci, 'pagu_bos_id', 'Tahun anggaran sudah dikunci.');
            $this->require((int) $record->tanggal->format('Y') === (int) $pagu->tahunAnggaran->tahun, 'tanggal', 'Tanggal harus berada dalam tahun anggaran pagu.');
        }
        if ($record instanceof PaguBos) {
            $year = TahunAnggaran::findOrFail($record->tahun_anggaran_id);
            $this->require(! $year->dikunci, 'tahun_anggaran_id', 'Tahun anggaran sudah dikunci.');
            $this->require((int) $record->tanggal->format('Y') === (int) $year->tahun, 'tanggal', 'Tanggal penetapan harus sesuai tahun anggaran.');
            $this->require(! PaguBos::where('sekolah_id', $record->sekolah_id)->where('tahun_anggaran_id', $record->tahun_anggaran_id)->where('jenis_bos_id', $record->jenis_bos_id)->when($record->exists, fn ($q) => $q->whereKeyNot($record->id))->exists(), 'sekolah_id', 'Pagu sekolah/tahun/jenis ini sudah ada.');
            if (! $record->exists || ! app(FinancialWorkflow::class)->isWriting()) $record->pagu_berlaku = $record->pagu_awal;
        }
        if ($record instanceof PerubahanPagu) {
            if (! $record->exists) $record->nilai_lama = $record->pagu->pagu_berlaku;
            $this->require(filled($record->alasan), 'alasan', 'Alasan perubahan wajib diisi.');
        }
        if ($record instanceof PenyaluranBos) {
            $stage = TahapPenyaluran::findOrFail($record->tahap_penyaluran_id);
            $this->require($stage->tahun_anggaran_id === $pagu->tahun_anggaran_id && $stage->jenis_bos_id === $pagu->jenis_bos_id, 'tahap_penyaluran_id', 'Tahap tidak sesuai tahun/jenis BOS pagu.');
        }
        if (in_array($record->getTable(), ['penyaluran_bos', 'penerimaan_bos', 'realisasi_bos'])) {
            if ($record->koreksi_atas_id) {
                $original = $record->newQuery()->lockForUpdate()->findOrFail($record->koreksi_atas_id);
                $this->require($original->status === 'terverifikasi' && ! $original->koreksi_atas_id && $original->id !== $record->id && $original->pagu_bos_id === $record->pagu_bos_id, 'koreksi_atas_id', 'Koreksi harus merujuk transaksi asli terverifikasi pada pagu yang sama.');
                $this->require(bccomp($record->nominal, bcmul($original->nominal, '-1', 2), 2) === 0 && bccomp($record->nominal, '0', 2) < 0, 'nominal', 'Koreksi pembalik harus tepat sebesar negatif nominal transaksi asli. Setelah diverifikasi, buat transaksi pengganti jika diperlukan.');
                $this->require(! $record->newQuery()->where('koreksi_atas_id', $original->id)->when($record->exists, fn ($q) => $q->whereKeyNot($record->id))->exists(), 'koreksi_atas_id', 'Transaksi ini sudah memiliki koreksi pembalik.');
                if ($record instanceof RealisasiBos) $this->require($record->rkas_detail_id === $original->rkas_detail_id, 'rkas_detail_id', 'Koreksi harus memakai rincian RKAS transaksi asli.');
                if ($record instanceof PenyaluranBos) $this->require($record->tahap_penyaluran_id === $original->tahap_penyaluran_id, 'tahap_penyaluran_id', 'Koreksi harus memakai tahap transaksi asli.');
                if ($record instanceof PenerimaanBos) $this->require($record->penyaluran_bos_id === $original->penyaluran_bos_id, 'penyaluran_bos_id', 'Koreksi harus memakai penyaluran terkait transaksi asli.');
            } else $this->require(bccomp($record->nominal, '0', 2) >= 0, 'nominal', 'Nominal negatif hanya diizinkan untuk koreksi pembalik yang tertaut.');
        }
        if ($record instanceof PenerimaanBos && $record->penyaluran_bos_id) {
            $distribution = PenyaluranBos::findOrFail($record->penyaluran_bos_id);
            $this->require($distribution->pagu_bos_id === $record->pagu_bos_id && $distribution->status === 'terverifikasi', 'penyaluran_bos_id', 'Penyaluran harus terverifikasi dan berasal dari pagu yang sama.');
        }
        if ($record instanceof Rkas) $this->require(! Rkas::where('pagu_bos_id', $record->pagu_bos_id)->where('versi', $record->versi)->when($record->exists, fn ($q) => $q->whereKeyNot($record->id))->exists(), 'versi', 'Versi RKAS sudah tersedia.');
        if ($record instanceof RkasDetail) {
            $rkas = Rkas::query()->lockForUpdate()->findOrFail($record->rkas_id);
            $this->require(in_array($rkas->status, ['draft', 'perlu_perbaikan']), 'rkas_id', 'Rincian RKAS diajukan/terverifikasi dikunci; buat versi baru.');
            $this->require(! $rkas->pagu->tahunAnggaran->dikunci, 'rkas_id', 'Tahun anggaran sudah dikunci.');
            $component = KomponenBelanja::findOrFail($record->komponen_belanja_id);
            $this->require($rkas->pagu->tahun_anggaran_id === $component->tahun_anggaran_id, 'komponen_belanja_id', 'Komponen tidak sesuai tahun RKAS.');
            $this->require(! RkasDetail::where('rkas_id', $rkas->id)->where('komponen_belanja_id', $component->id)->when($record->exists, fn ($q) => $q->whereKeyNot($record->id))->exists(), 'komponen_belanja_id', 'Komponen sudah tersedia pada versi RKAS ini.');
        }
        if ($record instanceof RealisasiBos) {
            $detail = RkasDetail::with('rkas')->findOrFail($record->rkas_detail_id);
            $this->require($detail->rkas->pagu_bos_id === $record->pagu_bos_id && $detail->rkas->status === 'terverifikasi', 'rkas_detail_id', 'Rincian harus berasal dari RKAS terverifikasi pada pagu ini.');
            $this->require($record->tanggal->format('Y-m-d') >= $detail->rkas->tanggal->format('Y-m-d'), 'tanggal', 'Tanggal realisasi tidak boleh sebelum tanggal RKAS rujukan.');
            $start = substr($record->getAttributes()['periode_awal'], 0, 10); $end = substr($record->getAttributes()['periode_akhir'], 0, 10);
            $this->require($start <= $end, 'periode_akhir', 'Akhir periode harus setelah awal periode.');
            $this->require(substr($start, 0, 4) === (string) $pagu->tahunAnggaran->tahun && substr($end, 0, 4) === (string) $pagu->tahunAnggaran->tahun, 'periode_awal', 'Periode harus berada pada tahun anggaran pagu.');
            $this->require($record->tanggal->format('Y-m-d') >= $start && $record->tanggal->format('Y-m-d') <= $end, 'tanggal', 'Tanggal harus berada dalam periode laporan.');
            if ($record->mode === 'transaksi') $this->require($start === $end, 'periode_awal', 'Mode transaksi memakai satu tanggal; awal dan akhir periode harus sama.');
            $overlap = RealisasiBos::where('pagu_bos_id', $record->pagu_bos_id)->whereNull('koreksi_atas_id')->whereDoesntHave('corrections', fn ($q) => $q->where('status', 'terverifikasi'))->whereHas('rincian', fn ($q) => $q->where('komponen_belanja_id', $detail->komponen_belanja_id))
                ->where('periode_awal', '<=', $end)->where('periode_akhir', '>=', $start)
                ->when($record->exists, fn ($q) => $q->whereKeyNot($record->id))
                ->when($record->mode === 'transaksi', fn ($q) => $q->where('mode', 'rekap'))->exists();
            $this->require($record->koreksi_atas_id || ! $overlap, 'periode_awal', 'Periode bertumpang tindih dengan rekap/transaksi yang mewakili belanja sama.');
        }
        if ($record instanceof \App\Models\DataMurid) $this->require(! \App\Models\DataMurid::where('sekolah_id', $record->sekolah_id)->where('tahun_pendataan', $record->tahun_pendataan)->when($record->exists, fn ($q) => $q->whereKeyNot($record->id))->exists(), 'tahun_pendataan', 'Data murid sekolah/tahun sudah tersedia.');
    }

    private function require(bool $condition, string $field, string $message): void
    {
        if (! $condition) throw ValidationException::withMessages([$field => $message]);
    }
}
