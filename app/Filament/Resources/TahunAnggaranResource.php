<?php

namespace App\Filament\Resources;

class TahunAnggaranResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\TahunAnggaran::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => TahunAnggaranResource\Pages\ManageTahunAnggaran::route('/')]; }
}
