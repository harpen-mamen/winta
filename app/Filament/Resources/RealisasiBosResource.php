<?php

namespace App\Filament\Resources;

class RealisasiBosResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\RealisasiBos::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => RealisasiBosResource\Pages\ManageRealisasiBos::route('/')]; }
}
