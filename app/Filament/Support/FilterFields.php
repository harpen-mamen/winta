<?php

namespace App\Filament\Support;

use App\Services\ModuleFields;
use Filament\Forms\Components\{Select, DatePicker};

class FilterFields
{
    public static function make(): array
    {
        $fields = [Select::make('lingkup')->label('Lingkup Data')->options(['semua' => 'Semua (termasuk demo)', 'resmi' => 'Data resmi saja', 'demo' => 'Data demo saja'])->default('semua')->selectablePlaceholder(false)];
        foreach (['tahun_anggaran_id' => 'TahunAnggaran:tahun', 'jenis_bos_id' => 'JenisBos:nama', 'kecamatan_id' => 'Kecamatan:nama', 'sekolah_id' => 'Sekolah:nama', 'tahap_penyaluran_id' => 'TahapPenyaluran:label'] as $name => $type) {
            $fields[] = Select::make($name)->label(ModuleFields::label($name))->options(fn () => ModuleFields::options($type))->searchable()->placeholder('Semua');
        }
        $fields[] = DatePicker::make('mulai')->label('Awal Periode');
        $fields[] = DatePicker::make('sampai')->label('Akhir Periode');
        return $fields;
    }
}
