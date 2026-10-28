<?php

namespace App\Models;

class RealisasiBos extends FinancialRecord
{
    protected $table = 'realisasi_bos';
    protected $fillable = ['pagu_bos_id', 'rkas_detail_id', 'mode', 'periode_awal', 'periode_akhir', 'tanggal', 'nominal', 'referensi', 'sumber', 'catatan', 'koreksi_atas_id'];
    protected function casts(): array { return ['pagu_bos_id' => 'integer', 'rkas_detail_id' => 'integer', 'periode_awal' => 'date', 'periode_akhir' => 'date', 'tanggal' => 'date', 'nominal' => 'decimal:2', 'koreksi_atas_id' => 'integer']; }
    public function pagu() { return $this->belongsTo(PaguBos::class, 'pagu_bos_id'); }
    public function rincian() { return $this->belongsTo(RkasDetail::class, 'rkas_detail_id'); }
    public function koreksi() { return $this->belongsTo(RealisasiBos::class, 'koreksi_atas_id'); }
    public function corrections() { return $this->hasMany(RealisasiBos::class, 'koreksi_atas_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
