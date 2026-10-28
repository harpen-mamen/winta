<?php

namespace App\Filament\Widgets;

use App\Data\MonitoringFilter;
use App\Services\MonitoringService;
use Filament\Widgets\Widget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class MonitoringOverview extends Widget
{
    use InteractsWithPageFilters;
    protected string $view = 'filament.widgets.monitoring-overview';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = -20;
    public function summary(): array { return app(MonitoringService::class)->summary(new MonitoringFilter($this->pageFilters ?? [])); }
}
