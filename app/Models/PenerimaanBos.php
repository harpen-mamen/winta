<?php

namespace App\Models;

class PenerimaanBos extends FinancialRecord
{
    protected $table = 'penerimaan_bos';
    protected $fillable = ['pagu_bos_id', 'penyaluran_bos_id', 'nominal', 'tanggal', 'referensi', 'sumber', 'catatan', 'koreksi_atas_id'];
    protected function casts(): array { return ['pagu_bos_id' => 'integer', 'penyaluran_bos_id' => 'integer', 'nominal' => 'decimal:2', 'tanggal' => 'date', 'koreksi_atas_id' => 'integer']; }
    public function pagu() { return $this->belongsTo(PaguBos::class, 'pagu_bos_id'); }
    public function penyaluran() { return $this->belongsTo(PenyaluranBos::class, 'penyaluran_bos_id'); }
    public function koreksi() { return $this->belongsTo(PenerimaanBos::class, 'koreksi_atas_id'); }
    public function corrections() { return $this->hasMany(PenerimaanBos::class, 'koreksi_atas_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
