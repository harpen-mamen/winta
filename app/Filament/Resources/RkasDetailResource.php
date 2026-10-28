<?php

namespace App\Filament\Resources;

class RkasDetailResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\RkasDetail::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => RkasDetailResource\Pages\ManageRkasDetail::route('/')]; }
}
