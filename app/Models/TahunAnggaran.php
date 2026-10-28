<?php

namespace App\Models;

class TahunAnggaran extends BosModel
{
    protected $table = 'tahun_anggarans';
    protected $fillable = ['tahun', 'aktif', 'dikunci'];
    protected function casts(): array { return ['tahun' => 'integer', 'aktif' => 'boolean', 'dikunci' => 'boolean']; }

    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
