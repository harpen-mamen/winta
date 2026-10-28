<?php

namespace App\Models;

class AuditLog extends BosModel
{
    protected $table = 'audit_logs';
    protected $fillable = ['target_type', 'target_id', 'aksi', 'sebelum', 'sesudah', 'user_id'];
    protected function casts(): array { return ['target_id' => 'integer', 'user_id' => 'integer', 'created_at' => 'datetime', 'sebelum' => 'array', 'sesudah' => 'array']; }

    public function target() { return $this->morphTo(); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
