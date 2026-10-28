<?php

namespace App\Filament\Pages;

use App\Filament\Support\FilterFields;
use App\Filament\Widgets;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasFiltersForm;
    protected static ?string $title = 'Dashboard Monitoring BOS';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static string | \UnitEnum | null $navigationGroup = 'MENU UTAMA';

    public static function canAccess(): bool { return auth()->user()?->can('monitoring.view') ?? false; }
    public function getSubheading(): ?string { return 'Bidang Pembinaan SMP · Dinas Pendidikan dan Kebudayaan Daerah Kabupaten Kepulauan Sangihe'; }
    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([Section::make('Sesuaikan tampilan data')->description('Pilih lingkup demo/resmi dan periode. Hanya data terverifikasi yang dijumlahkan; saldo dihitung kumulatif sampai akhir periode.')->icon('heroicon-o-adjustments-horizontal')->schema(FilterFields::make())->columns(['default' => 2, 'xl' => 4])->columnSpanFull()])->columns(1);
    }
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Grid::make(1)->schema(fn () => $this->getWidgetsSchemaComponents([Widgets\MonitoringOverview::class])),
            $this->getFiltersFormContentComponent(),
            $this->getWidgetsContentComponent(),
        ]);
    }
    public function getWidgets(): array
    {
        return [Widgets\GettingStarted::class, Widgets\BosStats::class, Widgets\PaguChart::class, Widgets\TahapChart::class, Widgets\RkasChart::class, Widgets\PenerimaanTrendChart::class, Widgets\RealisasiTrendChart::class, Widgets\KecamatanChart::class, Widgets\KomponenChart::class, Widgets\VerifikasiChart::class];
    }
}
