<?php

namespace App\Filament\Resources;

class SaldoAwalBosResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\SaldoAwalBos::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => SaldoAwalBosResource\Pages\ManageSaldoAwalBos::route('/')]; }
}
