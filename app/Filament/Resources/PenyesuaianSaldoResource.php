<?php

namespace App\Filament\Resources;

class PenyesuaianSaldoResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\PenyesuaianSaldo::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => PenyesuaianSaldoResource\Pages\ManagePenyesuaianSaldo::route('/')]; }
}
