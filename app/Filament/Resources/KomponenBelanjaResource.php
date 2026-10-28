<?php

namespace App\Filament\Resources;

class KomponenBelanjaResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\KomponenBelanja::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => KomponenBelanjaResource\Pages\ManageKomponenBelanja::route('/')]; }
}
