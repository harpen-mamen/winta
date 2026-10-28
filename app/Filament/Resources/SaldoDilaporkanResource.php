<?php

namespace App\Filament\Resources;

class SaldoDilaporkanResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\SaldoDilaporkan::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => SaldoDilaporkanResource\Pages\ManageSaldoDilaporkan::route('/')]; }
}
