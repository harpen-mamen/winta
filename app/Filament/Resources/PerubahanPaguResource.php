<?php

namespace App\Filament\Resources;

class PerubahanPaguResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\PerubahanPagu::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => PerubahanPaguResource\Pages\ManagePerubahanPagu::route('/')]; }
}
