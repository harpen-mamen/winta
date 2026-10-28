<?php

namespace App\Filament\Pages;

class GrafikAnalitik extends Dashboard
{
    protected static string $routePath = '/grafik-analitik';
    protected static ?string $title = 'Grafik Analitik';
    protected static ?string $navigationLabel = 'Grafik Analitik';
    protected static string | \UnitEnum | null $navigationGroup = 'MONITORING';
}
