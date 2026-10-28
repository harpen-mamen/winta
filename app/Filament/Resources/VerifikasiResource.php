<?php

namespace App\Filament\Resources;

class VerifikasiResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\Verifikasi::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => VerifikasiResource\Pages\ManageVerifikasi::route('/')]; }
}
