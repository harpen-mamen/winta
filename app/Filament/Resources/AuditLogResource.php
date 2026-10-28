<?php

namespace App\Filament\Resources;

class AuditLogResource extends \App\Filament\Support\BosResource
{
    protected static ?string $model = \App\Models\AuditLog::class;
    protected static bool $isDiscovered = true;
    public static function getPages(): array { return ['index' => AuditLogResource\Pages\ManageAuditLog::route('/')]; }
}
