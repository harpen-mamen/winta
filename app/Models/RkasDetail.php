<?php

namespace App\Models;

class RkasDetail extends BosModel
{
    protected $table = 'rkas_details';
    protected $fillable = ['rkas_id', 'komponen_belanja_id', 'nominal'];
    protected function casts(): array { return ['rkas_id' => 'integer', 'komponen_belanja_id' => 'integer', 'nominal' => 'decimal:2']; }
    public function rkas() { return $this->belongsTo(Rkas::class, 'rkas_id'); }
    public function komponen() { return $this->belongsTo(KomponenBelanja::class, 'komponen_belanja_id'); }
    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
