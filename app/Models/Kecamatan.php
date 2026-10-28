<?php

namespace App\Models;

class Kecamatan extends BosModel
{
    protected $table = 'kecamatans';
    protected $fillable = ['kode', 'nama', 'aktif'];
    protected function casts(): array { return ['aktif' => 'boolean']; }

    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
