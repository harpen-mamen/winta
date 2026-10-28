<?php

namespace App\Models;

class Sekolah extends BosModel
{
    protected $table = 'sekolahs';
    protected $fillable = ['npsn', 'nama', 'status_sekolah', 'kecamatan_id', 'alamat', 'aktif', 'is_demo'];
    protected function casts(): array { return ['kecamatan_id' => 'integer', 'aktif' => 'boolean', 'is_demo' => 'boolean']; }
    public function kecamatan() { return $this->belongsTo(Kecamatan::class, 'kecamatan_id'); }
    public function dataMurids() { return $this->hasMany(DataMurid::class, 'sekolah_id'); }
    public function pagus() { return $this->hasMany(PaguBos::class, 'sekolah_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
