<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

abstract class BosModel extends Model
{
    public function save(array $options = [])
    {
        return DB::transaction(fn () => parent::save($options));
    }

    protected static function booted(): void
    {
        static::saving(function (BosModel $record) {
            if (in_array($record->getTable(), ['audit_logs', 'verifikasis']) && $record->exists) {
                throw ValidationException::withMessages(['data' => 'Riwayat tidak dapat diubah.']);
            }
            if ($record instanceof Evaluasi && ! $record->exists) $record->user_id = auth()->id();
            app(\App\Services\RecordValidation::class)->validate($record);
        });
        static::created(fn (BosModel $record) => $record->writeAudit('dibuat', null, $record->getAttributes()));
        static::updated(fn (BosModel $record) => $record->writeAudit('diubah', $record->getRawOriginal(), $record->getAttributes()));
        static::deleting(function (BosModel $record) {
            throw ValidationException::withMessages(['data' => 'Data dipertahankan untuk riwayat. Nonaktifkan master atau ajukan koreksi.']);
        });
    }

    public function writeAudit(string $action, ?array $before, ?array $after): void
    {
        if (in_array($this->getTable(), ['audit_logs', 'verifikasis'])) return;
        AuditLog::create([
            'target_type' => $this->getMorphClass(), 'target_id' => $this->getKey(),
            'user_id' => auth()->id(), 'aksi' => $action, 'sebelum' => $before, 'sesudah' => $after,
        ]);
    }
}
