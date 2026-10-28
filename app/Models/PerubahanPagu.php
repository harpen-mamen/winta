<?php

namespace App\Models;

class PerubahanPagu extends FinancialRecord
{
    protected $table = 'perubahan_pagu';
    protected $fillable = ['pagu_bos_id', 'nilai_baru', 'tanggal', 'sumber', 'alasan', 'catatan', 'nilai_lama'];
    protected function casts(): array { return ['pagu_bos_id' => 'integer', 'nilai_baru' => 'decimal:2', 'tanggal' => 'date', 'nilai_lama' => 'decimal:2']; }
    public function pagu() { return $this->belongsTo(PaguBos::class, 'pagu_bos_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
