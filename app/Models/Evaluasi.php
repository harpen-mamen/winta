<?php

namespace App\Models;

class Evaluasi extends BosModel
{
    protected $table = 'evaluasis';
    protected $fillable = ['pagu_bos_id', 'catatan', 'tanggal'];
    protected function casts(): array { return ['pagu_bos_id' => 'integer', 'tanggal' => 'date']; }
    public function pagu() { return $this->belongsTo(PaguBos::class, 'pagu_bos_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
