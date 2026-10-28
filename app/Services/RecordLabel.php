<?php

namespace App\Services;

use App\Models\{PaguBos, Rkas, RkasDetail, TahapPenyaluran};
use Illuminate\Database\Eloquent\Model;

class RecordLabel
{
    public function for(Model $record): string
    {
        if ($record instanceof PaguBos) return ($record->sekolah?->nama ?? 'Sekolah').' · '.($record->tahunAnggaran?->tahun ?? '').' · '.($record->jenisBos?->nama ?? '').' (#'.$record->id.')';
        if ($record instanceof Rkas) return $record->pagu?->label.' · RKAS v'.$record->versi;
        if ($record instanceof RkasDetail) return $record->rkas?->label.' · '.$record->komponen?->nama;
        if ($record instanceof TahapPenyaluran) return $record->nama.' · '.$record->tahunAnggaran?->tahun.' · '.$record->jenisBos?->nama;
        return (string) ($record->nama ?? $record->referensi ?? $record->tahun ?? '#'.$record->getKey());
    }
}
