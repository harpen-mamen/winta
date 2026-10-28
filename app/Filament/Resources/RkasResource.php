<?php

namespace App\Filament\Resources;

class RkasResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\Rkas::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => RkasResource\Pages\ManageRkas::route('/')]; }
}
