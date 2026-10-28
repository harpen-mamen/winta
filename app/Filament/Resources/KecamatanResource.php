<?php

namespace App\Filament\Resources;

class KecamatanResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\Kecamatan::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => KecamatanResource\Pages\ManageKecamatan::route('/')]; }
}
