<?php

namespace App\Filament\Support;

use Filament\Resources\Pages\ManageRecords;
use Filament\Actions\{CreateAction, Action};

abstract class ManageBosRecords extends ManageRecords
{
    protected function getHeaderActions(): array
    {
        $resource = static::getResource();
        return [
            CreateAction::make()->label('Tambah '.$resource::getModelLabel())->visible($resource::canCreate()),
            Action::make('ekspor')->label('Ekspor XLSX')->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => auth()->user()->can('reports.export') && auth()->user()->can($resource::key().'.view'))
                ->url(fn () => route('module.export', ['module' => $resource::key(), 'format' => 'xlsx', 'table_filters' => $this->tableFilters ?? []])),
        ];
    }
}
