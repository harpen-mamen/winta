<?php
namespace App\Filament\Resources\UserResource\Pages;

class ManageUsers extends \Filament\Resources\Pages\ManageRecords
{
    protected static string $resource = \App\Filament\Resources\UserResource::class;
    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()->label('Tambah Pengguna')->action(fn (array $data) => app(\App\Services\UserAdministration::class)->save($data))];
    }
}
