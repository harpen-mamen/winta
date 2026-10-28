<?php

namespace App\Filament\Resources;

class TahapPenyaluranResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\TahapPenyaluran::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => TahapPenyaluranResource\Pages\ManageTahapPenyaluran::route('/')]; }
}
