<?php

namespace App\Filament\Resources;

class SekolahResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\Sekolah::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => SekolahResource\Pages\ManageSekolah::route('/')]; }
}
