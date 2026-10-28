<?php

namespace App\Filament\Pages;

use App\Services\MonitoringService;

class Verifikasi extends \App\Filament\Support\FilteredPage
{
    protected static bool $isDiscovered = true;
    protected static ?string $title = 'Antrean Verifikasi';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-check-badge';
    protected string $view = 'filament.pages.verifikasi';
    public static function canAccess(): bool { return auth()->user()?->can('finance.verify') ?? false; }
    public function queue(): array
    {
        $rows = [];
        foreach (config('bos-modules') as $module) if ($module['finance']) {
            foreach (app(MonitoringService::class)->query($module['model'], $this->filter(), false, false)->where('status', 'diajukan')->get() as $record) {
                $resource = 'App\\Filament\\Resources\\'.class_basename($record).'Resource';
                $rows[] = ['modul' => $module['label'], 'label' => $record->label, 'penginput' => $record->creator?->name, 'dokumen' => $record->documents()->count(), 'url' => $resource::getUrl()];
            }
        }
        return $rows;
    }
}
