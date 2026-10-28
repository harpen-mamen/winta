<?php

namespace App\Services;

use App\Models\{FinancialRecord, PaguBos, PerubahanPagu, Rkas, Verifikasi};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FinancialWorkflow
{
    private bool $writing = false;
    public function isWriting(): bool { return $this->writing; }

    public function transition(FinancialRecord $record, string $status, ?string $note = null): void
    {
        DB::transaction(function () use ($record, $status, $note) {
            $fresh = $record->newQuery()->lockForUpdate()->findOrFail($record->id);
            $submit = $status === 'diajukan';
            Gate::authorize($submit ? 'submit' : 'verify', $fresh);
            $allowed = $submit ? ['draft', 'perlu_perbaikan'] : ['diajukan'];
            if (! in_array($fresh->status, $allowed) || ! in_array($status, ['diajukan', 'terverifikasi', 'perlu_perbaikan'])) {
                throw ValidationException::withMessages(['status' => 'Transisi status tidak diizinkan.']);
            }
            if (! $submit && $fresh->created_by === auth()->id()) throw ValidationException::withMessages(['status' => 'Penginput tidak boleh memverifikasi data sendiri.']);
            if ($status === 'perlu_perbaikan' && blank($note)) throw ValidationException::withMessages(['catatan' => 'Catatan perbaikan wajib diisi.']);
            if ($submit && $fresh->documents()->count() === 0) throw ValidationException::withMessages(['dokumen' => 'Unggah minimal satu dokumen pendukung sebelum pengajuan.']);
            if ($fresh instanceof Rkas && $fresh->details()->count() === 0) throw ValidationException::withMessages(['rkas' => 'Tambahkan rincian RKAS sebelum mengajukan.']);
            $before = $fresh->status;
            $this->writing = true;
            try {
                if ($status === 'terverifikasi' && $fresh instanceof PerubahanPagu) {
                    $pagu = PaguBos::query()->lockForUpdate()->findOrFail($fresh->pagu_bos_id);
                    if (bccomp($pagu->pagu_berlaku, $fresh->nilai_lama, 2) !== 0) throw ValidationException::withMessages(['nilai_baru' => 'Pagu telah berubah. Buat usulan perubahan baru berdasarkan nilai terbaru.']);
                    $latest = $pagu->perubahans()->where('status', 'terverifikasi')->orderByDesc('tanggal')->first();
                    if ($fresh->tanggal->lt($latest?->tanggal ?? $pagu->tanggal)) throw ValidationException::withMessages(['tanggal' => 'Tanggal perubahan tidak boleh mendahului penetapan/perubahan terakhir.']);
                    $pagu->pagu_berlaku = $fresh->nilai_baru;
                    $pagu->save();
                }
                $fresh->forceFill(['status' => $status, 'verified_by' => $submit ? null : auth()->id(), 'verified_at' => $submit ? null : now()])->save();
                Verifikasi::create(['target_type' => $fresh->getMorphClass(), 'target_id' => $fresh->id, 'status_sebelum' => $before, 'status_sesudah' => $status, 'catatan' => $note, 'user_id' => auth()->id()]);
            } finally { $this->writing = false; }
            $record->refresh();
        });
    }
}
