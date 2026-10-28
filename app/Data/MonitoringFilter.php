<?php

namespace App\Data;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

final class MonitoringFilter
{
    public readonly array $values;
    public function __construct(array $values = [])
    {
        $this->values = validator(array_filter($values, fn ($value) => $value !== null && $value !== ''), [
            'lingkup' => ['nullable', Rule::in(['semua', 'resmi', 'demo'])],
            'tahun_anggaran_id' => ['nullable', Rule::exists('tahun_anggarans', 'id')],
            'jenis_bos_id' => ['nullable', Rule::exists('jenis_bos', 'id')],
            'kecamatan_id' => ['nullable', Rule::exists('kecamatans', 'id')],
            'sekolah_id' => ['nullable', Rule::exists('sekolahs', 'id')],
            'tahap_penyaluran_id' => ['nullable', Rule::exists('tahap_penyalurans', 'id')],
            'mulai' => ['nullable', 'date_format:Y-m-d'], 'sampai' => ['nullable', 'date_format:Y-m-d'],
        ])->validate();
        if (! empty($this->values['mulai']) && ! empty($this->values['sampai']) && $this->values['mulai'] > $this->values['sampai']) throw \Illuminate\Validation\ValidationException::withMessages(['sampai' => 'Akhir periode harus setelah awal periode.']);
    }
    public function pagu(Builder $query): Builder
    {
        foreach (['tahun_anggaran_id', 'jenis_bos_id', 'sekolah_id'] as $key) if (! empty($this->values[$key])) $query->where($key, $this->values[$key]);
        if (! empty($this->values['kecamatan_id'])) $query->whereHas('sekolah', fn ($q) => $q->where('kecamatan_id', $this->values['kecamatan_id']));
        if (in_array($this->values['lingkup'] ?? null, ['demo', 'resmi'])) $query->whereHas('sekolah', fn ($q) => $q->where('is_demo', $this->values['lingkup'] === 'demo'));
        return $query;
    }
    public function schools(Builder $query): Builder
    {
        if (in_array($this->values['lingkup'] ?? null, ['demo', 'resmi'])) $query->where('is_demo', $this->values['lingkup'] === 'demo');
        return $query;
    }
    public function dates(Builder $query, string $column = 'tanggal', bool $cumulative = false): Builder
    {
        if (! $cumulative && ! empty($this->values['mulai'])) $query->whereDate($column, '>=', $this->values['mulai']);
        if (! empty($this->values['sampai'])) $query->whereDate($column, '<=', $this->values['sampai']);
        return $query;
    }
}
