<?php

namespace App\Models;

use App\Services\FinancialWorkflow;
use Illuminate\Validation\ValidationException;

abstract class FinancialRecord extends BosModel
{
    protected static function booted(): void
    {
        parent::booted();
        static::saving(function (FinancialRecord $record) {
            $workflow = app(FinancialWorkflow::class);
            if (! $workflow->isWriting()) {
                if ($record->exists && in_array($record->getRawOriginal('status'), ['terverifikasi', 'diajukan'])) {
                    throw ValidationException::withMessages(['data' => 'Data diajukan/terverifikasi dikunci. Gunakan perubahan pagu atau transaksi koreksi yang terdokumentasi.']);
                }
                if ($record->isDirty(['status', 'verified_by', 'verified_at'])) {
                    throw ValidationException::withMessages(['data' => 'Status hanya boleh diubah melalui aksi verifikasi.']);
                }
            }
            if (! $record->exists) {
                $record->created_by = auth()->id();
                $record->status = 'draft';
            }
        });
    }

    public function documents() { return $this->morphMany(DokumenPendukung::class, 'target'); }
    public function verifikasis() { return $this->morphMany(Verifikasi::class, 'target'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
