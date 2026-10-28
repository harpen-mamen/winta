<?php

namespace App\Models;

class Rkas extends FinancialRecord
{
    protected $table = 'rkas';
    protected $fillable = ['pagu_bos_id', 'versi', 'tanggal', 'sumber', 'catatan'];
    protected function casts(): array { return ['pagu_bos_id' => 'integer', 'versi' => 'integer', 'tanggal' => 'date']; }
    public function pagu() { return $this->belongsTo(PaguBos::class, 'pagu_bos_id'); }
    public function details() { return $this->hasMany(RkasDetail::class, 'rkas_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
