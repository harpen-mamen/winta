<?php

namespace App\Models;

class JenisBos extends BosModel
{
    protected $table = 'jenis_bos';
    protected $fillable = ['kode', 'nama', 'sumber', 'aktif'];
    protected function casts(): array { return ['aktif' => 'boolean']; }

    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
