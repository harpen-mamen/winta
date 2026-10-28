<?php

namespace App\Filament\Support;

use App\Data\MonitoringFilter;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

abstract class FilteredPage extends Page
{
    protected static bool $isDiscovered = false;
    protected static string | \UnitEnum | null $navigationGroup = 'MONITORING';
    public ?array $filters = [];
    public static function canAccess(): bool { return auth()->user()?->can('monitoring.view') ?? false; }
    public function mount(): void { $this->form->fill(); }
    public function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Filter Data')->schema(FilterFields::make())->columns(4)])->statePath('filters')->live();
    }
    public function filter(): MonitoringFilter { return new MonitoringFilter($this->filters ?? []); }
}
