<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use App\Filament\Support\FilterFields;

class Laporan extends \App\Filament\Support\FilteredPage
{
    protected static bool $isDiscovered = true;
    protected static ?string $title = 'Laporan';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected string $view = 'filament.pages.laporan';
    public function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Jenis dan Filter Laporan')->schema([
            Select::make('jenis_laporan')->label('Jenis Laporan')->options(ReportService::types())->default('pagu_pencairan')->required(), ...FilterFields::make(),
        ])->columns(4)])->statePath('filters')->live();
    }
    public function report(): array { return app(ReportService::class)->dataset($this->filters['jenis_laporan'] ?? 'pagu_pencairan', $this->filter()); }
    public function downloadUrl(string $format): string { return route('reports.download', ['format' => $format, 'jenis' => $this->filters['jenis_laporan'] ?? 'pagu_pencairan', 'filter' => $this->filter()->values]); }
}
