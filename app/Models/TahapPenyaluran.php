<?php

namespace App\Models;

class TahapPenyaluran extends BosModel
{
    protected $table = 'tahap_penyalurans';
    protected $fillable = ['tahun_anggaran_id', 'jenis_bos_id', 'kode', 'nama', 'urutan', 'jadwal'];
    protected function casts(): array { return ['tahun_anggaran_id' => 'integer', 'jenis_bos_id' => 'integer', 'urutan' => 'integer', 'jadwal' => 'date']; }
    public function tahunAnggaran() { return $this->belongsTo(TahunAnggaran::class, 'tahun_anggaran_id'); }
    public function jenisBos() { return $this->belongsTo(JenisBos::class, 'jenis_bos_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
