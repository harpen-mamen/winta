<?php

namespace App\Filament\Resources;

class DokumenPendukungResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\DokumenPendukung::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => DokumenPendukungResource\Pages\ManageDokumenPendukung::route('/')]; }
}
