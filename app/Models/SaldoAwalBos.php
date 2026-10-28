<?php

namespace App\Models;

class SaldoAwalBos extends FinancialRecord
{
    protected $table = 'saldo_awal_bos';
    protected $fillable = ['pagu_bos_id', 'nominal', 'tanggal', 'referensi', 'sumber', 'catatan'];
    protected function casts(): array { return ['pagu_bos_id' => 'integer', 'nominal' => 'decimal:2', 'tanggal' => 'date']; }
    public function pagu() { return $this->belongsTo(PaguBos::class, 'pagu_bos_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
