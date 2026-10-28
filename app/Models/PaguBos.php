<?php

namespace App\Models;

class PaguBos extends FinancialRecord
{
    protected $table = 'pagu_bos';
    protected $fillable = ['sekolah_id', 'tahun_anggaran_id', 'jenis_bos_id', 'pagu_awal', 'tanggal', 'nomor_dokumen', 'sumber', 'catatan', 'pagu_berlaku'];
    protected function casts(): array { return ['sekolah_id' => 'integer', 'tahun_anggaran_id' => 'integer', 'jenis_bos_id' => 'integer', 'pagu_awal' => 'decimal:2', 'tanggal' => 'date', 'pagu_berlaku' => 'decimal:2']; }
    public function sekolah() { return $this->belongsTo(Sekolah::class, 'sekolah_id'); }
    public function tahunAnggaran() { return $this->belongsTo(TahunAnggaran::class, 'tahun_anggaran_id'); }
    public function jenisBos() { return $this->belongsTo(JenisBos::class, 'jenis_bos_id'); }
    public function perubahans() { return $this->hasMany(PerubahanPagu::class, 'pagu_bos_id'); }
    public function penyalurans() { return $this->hasMany(PenyaluranBos::class, 'pagu_bos_id'); }
    public function penerimaans() { return $this->hasMany(PenerimaanBos::class, 'pagu_bos_id'); }
    public function realisasis() { return $this->hasMany(RealisasiBos::class, 'pagu_bos_id'); }
    public function rkas() { return $this->hasMany(Rkas::class, 'pagu_bos_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
