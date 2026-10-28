<?php

namespace App\Filament\Resources;

class ImportBatchResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\ImportBatch::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => ImportBatchResource\Pages\ManageImportBatch::route('/')]; }
}
