<?php

namespace App\Filament\Resources;

class PenerimaanBosResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\PenerimaanBos::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => PenerimaanBosResource\Pages\ManagePenerimaanBos::route('/')]; }
}
