<?php
namespace App\Filament\Resources\RoleResource\Pages;

class ManageRoles extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = \App\Filament\Resources\RoleResource::class;
    protected function getHeaderActions(): array { return [\Filament\Actions\CreateAction::make()->label('Tambah Role')->after(fn ($record) => \App\Models\AuditLog::create(['target_type' => get_class($record), 'target_id' => $record->id, 'user_id' => auth()->id(), 'aksi' => 'buat_role', 'sesudah' => ['name' => $record->name]]))]; }
}
