<?php

namespace App\Filament\Resources;

class DataMuridResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\DataMurid::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => DataMuridResource\Pages\ManageDataMurid::route('/')]; }
}
