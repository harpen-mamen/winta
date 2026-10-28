<?php

namespace App\Filament\Pages;

class SaldoDana extends \App\Filament\Support\FilteredPage
{
    protected static bool $isDiscovered = true;
    protected static string | \UnitEnum | null $navigationGroup = 'DANA BOS';
    protected static ?string $title = 'Saldo Dana';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wallet';
    protected string $view = 'filament.pages.saldo';
    public function rows(): array { return app(\App\Services\MonitoringService::class)->rows($this->filter()); }
}
