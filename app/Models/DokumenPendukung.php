<?php

namespace App\Models;

class DokumenPendukung extends BosModel
{
    protected $table = 'dokumen_pendukung';
    protected $fillable = ['target_type', 'target_id', 'nama', 'path', 'mime', 'ukuran', 'checksum', 'uploaded_by'];
    protected function casts(): array { return ['ukuran' => 'integer', 'created_at' => 'datetime']; }

    public function target() { return $this->morphTo(); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
