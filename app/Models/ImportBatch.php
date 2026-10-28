<?php

namespace App\Models;

class ImportBatch extends BosModel
{
    protected $table = 'import_batches';
    protected $fillable = ['tipe', 'nama_file', 'checksum', 'status', 'jumlah', 'errors', 'user_id'];
    protected function casts(): array { return ['jumlah' => 'integer', 'created_at' => 'datetime', 'errors' => 'array']; }

    public function getLabelAttribute(): string { return app(\App\Services\RecordLabel::class)->for($this); }
}
