<?php

namespace App\Filament\Resources;

class PaguBosResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\PaguBos::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => PaguBosResource\Pages\ManagePaguBos::route('/')]; }
}
