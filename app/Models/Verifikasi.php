<?php

namespace App\Models;

class Verifikasi extends BosModel
{
    protected $table = 'verifikasis';
    protected $fillable = ['target_type', 'target_id', 'status_sebelum', 'status_sesudah', 'catatan', 'user_id'];
    protected function casts(): array { return ['target_id' => 'integer', 'user_id' => 'integer', 'created_at' => 'datetime']; }

    public function target() { return $this->morphTo(); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
