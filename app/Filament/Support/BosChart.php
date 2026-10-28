<?php

namespace App\Filament\Support;

use App\Data\MonitoringFilter;
use App\Services\ChartData;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

abstract class BosChart extends ChartWidget
{
    use InteractsWithPageFilters;
    protected string $chartKey;
    protected ?string $pollingInterval = null;
    protected ?string $maxHeight = '300px';
    protected ?string $emptyStateHeading = 'Belum ada data terverifikasi';
    protected ?string $emptyStateDescription = 'Lengkapi data atau sesuaikan filter monitoring.';
    protected function getType(): string { return in_array($this->chartKey, ['komponen', 'verifikasi']) ? 'doughnut' : (str_contains($this->chartKey, 'trend') ? 'line' : 'bar'); }
    protected function getData(): array { return app(ChartData::class)->get($this->chartKey, new MonitoringFilter($this->pageFilters ?? [])); }
}
