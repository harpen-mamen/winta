<?php

namespace App\Filament\Pages;

class Monitoring extends \App\Filament\Support\FilteredPage
{
    protected static bool $isDiscovered = true;
    protected static ?string $title = 'Temuan dan Selisih';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected string $view = 'filament.pages.monitoring';
    public function findings(): array { return app(\App\Services\MonitoringService::class)->findings($this->filter()); }
}
