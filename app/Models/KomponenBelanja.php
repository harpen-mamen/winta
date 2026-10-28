<?php

namespace App\Models;

class KomponenBelanja extends BosModel
{
    protected $table = 'komponen_belanja';
    protected $fillable = ['tahun_anggaran_id', 'kode', 'nama', 'sumber', 'aktif'];
    protected function casts(): array { return ['tahun_anggaran_id' => 'integer', 'aktif' => 'boolean']; }
    public function tahunAnggaran() { return $this->belongsTo(TahunAnggaran::class, 'tahun_anggaran_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
