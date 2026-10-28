<?php

namespace App\Services;

use Filament\Forms\Components\{TextInput, Textarea, Select, Toggle, DatePicker};

class ModuleFields
{
    public static function label(string $key): string
    {
        if ($key === 'created_by') return 'Dibuat Oleh';
        if ($key === 'koreksi_atas_id') return 'Transaksi Asli yang Dikoreksi';
        return ['pagu_bos_id' => 'Pagu / Sekolah / Tahun / Jenis BOS', 'tahun_anggaran_id' => 'Tahun Anggaran', 'jenis_bos_id' => 'Jenis BOS', 'rkas_detail_id' => 'Rincian RKAS', 'penyaluran_bos_id' => 'Penyaluran Terkait', 'status_sekolah' => 'Status Sekolah', 'is_demo' => 'Penanda Data Demo', 'npsn' => 'NPSN', 'rkas_id' => 'Versi RKAS', 'user_id' => 'Pengguna', 'created_at' => 'Dibuat Pada'][$key] ?? ucwords(str_replace(['_id', '_'], ['', ' '], $key));
    }
    public static function statuses(): array { return ['draft' => 'Draft', 'diajukan' => 'Diajukan', 'terverifikasi' => 'Terverifikasi', 'perlu_perbaikan' => 'Perlu Perbaikan']; }
    public static function options(string $type): array
    {
        [$model, $column] = explode(':', rtrim($type, '?')); $class = 'App\\Models\\'.$model;
        $query = $class::query();
        if (auth()->user()?->hasRole('Pimpinan Dinas') && is_subclass_of($class, \App\Models\FinancialRecord::class)) $query->where('status', 'terverifikasi');
        return $query->get()->mapWithKeys(fn ($r) => [$r->id => (string) $r->$column])->all();
    }
    public static function form(array $fields): array
    {
        $out = [];
        foreach ($fields as $name => $type) {
            $required = ! str_ends_with($type, '?'); $type = rtrim($type, '?');
            $field = match (true) {
                $type === 'bool' => Toggle::make($name)->default(in_array($name, ['aktif'])),
                $type === 'date' => DatePicker::make($name)->native(false)->displayFormat('d/m/Y'),
                $type === 'textarea' => Textarea::make($name)->rows(3)->maxLength(5000)->columnSpanFull(),
                str_starts_with($type, 'enum:') => Select::make($name)->options(array_combine(explode(',', substr($type, 5)), explode(',', substr($type, 5)))),
                str_contains($type, ':') => Select::make($name)->options(fn () => static::options($type))->searchable()->preload(),
                default => TextInput::make($name)->maxLength(255),
            };
            if (in_array($type, ['money', 'signed'])) $field->prefix('Rp')->inputMode('decimal')->rules(['regex:/^-?\d{1,16}(\.\d{1,2})?$/'])->helperText('Tanpa pemisah ribuan. Gunakan titik untuk desimal; contoh: 1250000.00');
            if (in_array($type, ['year', 'integer'])) $field->integer()->minValue($type === 'year' ? 2000 : 0)->maxValue($type === 'year' ? 2100 : 2147483647);
            if ($type === 'npsn') $field->length(8)->regex('/^[0-9]{8}$/')->unique(ignoreRecord: true);
            if ($name === 'koreksi_atas_id') $field->helperText('Kosongkan untuk transaksi biasa. Untuk pembalikan, pilih transaksi asli terverifikasi dan isi nominal tepat sebesar negatif nilai asli.');
            if ($type !== 'bool') $field->required($required);
            $out[] = $field->label(static::label($name));
        }
        return $out;
    }
    public static function display(mixed $state, string $type): string
    {
        $type = rtrim($type, '?');
        if ($state === null) return '—';
        if (in_array($type, ['money', 'signed'])) return Money::idr((string) $state);
        if ($type === 'bool') return $state ? 'Ya' : 'Tidak';
        if ($state instanceof \DateTimeInterface && in_array($type, ['date', 'datetime'])) return $state->format($type === 'date' ? 'd/m/Y' : 'd/m/Y H:i');
        if ($type === 'json') return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (str_contains($type, ':') && ! str_starts_with($type, 'enum:')) {
            [$model, $column] = explode(':', $type); $class = 'App\\Models\\'.$model;
            return (string) ($class::find($state)?->$column ?? '—');
        }
        return (string) $state;
    }
}
