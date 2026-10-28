<?php

namespace App\Filament\Resources;

class PenyaluranBosResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\PenyaluranBos::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => PenyaluranBosResource\Pages\ManagePenyaluranBos::route('/')]; }
}
