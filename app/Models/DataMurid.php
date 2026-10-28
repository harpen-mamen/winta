<?php

namespace App\Models;

class DataMurid extends BosModel
{
    protected $table = 'data_murids';
    protected $fillable = ['sekolah_id', 'tahun_pendataan', 'jumlah', 'sumber', 'tanggal'];
    protected function casts(): array { return ['sekolah_id' => 'integer', 'tahun_pendataan' => 'integer', 'jumlah' => 'integer', 'tanggal' => 'date']; }
    public function sekolah() { return $this->belongsTo(Sekolah::class, 'sekolah_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
