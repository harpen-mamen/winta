<?php

namespace App\Filament\Widgets;

class GettingStarted extends \Filament\Widgets\Widget
{
    protected string $view = 'filament.widgets.getting-started';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = -10;
    public static function canView(): bool { return \App\Models\Sekolah::count() === 0; }
}
