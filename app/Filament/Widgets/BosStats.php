<?php

namespace App\Filament\Widgets;

use App\Data\MonitoringFilter;
use App\Services\{MonitoringService, Money};
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class BosStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;
    protected static ?int $sort = -5;
    protected ?string $pollingInterval = null;
    protected ?string $description = 'Data terverifikasi sesuai filter. Sisa pagu belum diterima berbeda dengan saldo dana.';
    protected function getStats(): array
    {
        $filter = new MonitoringFilter($this->pageFilters ?? []); $service = app(MonitoringService::class); $data = $service->summary($filter);
        $money = fn ($value) => $value === null ? '—' : Money::idr((string) $value);
        $pct = fn ($value) => $value === null ? '—' : $value.'%';
        $followup = count(array_unique(array_column($service->findings($filter), 'sekolah')));
        return [
            Stat::make('Sekolah SMP Aktif', (string) $data['jumlah_sekolah'])->icon('heroicon-o-academic-cap')->extraAttributes(['class' => 'bos-stat-blue'])->description('Sekolah dalam lingkup terpilih'),
            Stat::make('Total Pagu BOS', $money($data['pagu']))->icon('heroicon-o-banknotes')->extraAttributes(['class' => 'bos-stat-teal'])->description('Pagu berlaku terverifikasi'),
            Stat::make('Dana Disalurkan', $money($data['penyaluran']))->icon('heroicon-o-arrow-up-right')->extraAttributes(['class' => 'bos-stat-violet'])->description('Dalam periode terpilih'),
            Stat::make('Dana Diterima', $money($data['penerimaan']))->icon('heroicon-o-arrow-down-left')->extraAttributes(['class' => 'bos-stat-sky'])->description('Penerimaan terkonfirmasi'),
            Stat::make('Realisasi Belanja', $money($data['realisasi']))->icon('heroicon-o-shopping-bag')->extraAttributes(['class' => 'bos-stat-teal'])->description('Belanja terverifikasi'),
            Stat::make('Sisa Pagu Belum Diterima', $money($data['belum_diterima']))->icon('heroicon-o-clock')->extraAttributes(['class' => 'bos-stat-amber'])->description('Pagu dikurangi penerimaan kumulatif'),
            Stat::make('Saldo Dana Terhitung', $money($data['saldo']))->icon('heroicon-o-wallet')->extraAttributes(['class' => 'bos-stat-blue'])->description('Kumulatif hingga akhir periode'),
            Stat::make('Persentase Penerimaan', $pct($data['persen_penerimaan']))->icon('heroicon-o-chart-pie')->extraAttributes(['class' => 'bos-stat-teal'])->description('Terhadap pagu berlaku'),
            Stat::make('Persentase Realisasi', $pct($data['persen_realisasi']))->icon('heroicon-o-chart-bar')->extraAttributes(['class' => 'bos-stat-violet'])->description('Terhadap anggaran RKAS'),
            Stat::make('Sekolah Perlu Tindak Lanjut', (string) $followup)->icon('heroicon-o-exclamation-triangle')->extraAttributes(['class' => 'bos-stat-rose'])->color($followup ? 'warning' : 'success')->description('Indikator untuk ditinjau'),
        ];
    }
}
