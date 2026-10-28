<?php

namespace App\Filament\Resources;

class EvaluasiResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\Evaluasi::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => EvaluasiResource\Pages\ManageEvaluasi::route('/')]; }
}
