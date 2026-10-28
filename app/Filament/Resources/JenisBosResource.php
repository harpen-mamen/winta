<?php

namespace App\Filament\Resources;

class JenisBosResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\JenisBos::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => JenisBosResource\Pages\ManageJenisBos::route('/')]; }
}
