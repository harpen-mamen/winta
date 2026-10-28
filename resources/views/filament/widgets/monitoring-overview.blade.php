<x-filament-widgets::widget>
    @php($summary = $this->summary())
    <section class="bos-overview" aria-label="Ringkasan monitoring dana BOS">
        <div class="bos-overview-copy">
            <span class="bos-eyebrow">BIDANG PEMBINAAN SMP · KEPULAUAN SANGIHE</span>
            <h2>Pantau dana BOS.<br>Lebih jelas, lebih terarah.</h2>
            <p>Pagu, penerimaan, dan penggunaan dana dalam satu ruang monitoring.</p>
            <div class="bos-overview-actions">
                <a href="{{ \App\Filament\Pages\Laporan::getUrl() }}"><x-filament::icon icon="heroicon-o-document-chart-bar" class="h-4 w-4" />Lihat Laporan</a>
                <a href="{{ \App\Filament\Pages\Monitoring::getUrl() }}"><x-filament::icon icon="heroicon-o-magnifying-glass" class="h-4 w-4" />Tinjau Monitoring</a>
            </div>
        </div>
        <div class="bos-progress-panel">
            <span class="bos-progress-title"><span class="bos-live-dot"></span>PROGRES SESUAI FILTER</span>
            @foreach (['persen_penerimaan' => 'Penerimaan dari pagu', 'persen_realisasi' => 'Realisasi dari RKAS'] as $key => $label)
                @php($percentage = $summary[$key])
                <div class="bos-progress-item">
                    <div><span>{{ $label }}</span><strong>{{ $percentage === null ? '—' : $percentage.'%' }}</strong></div>
                    <div class="bos-progress-track" role="img" aria-label="{{ $label }} {{ $percentage ?? 'belum tersedia' }} persen"><span style="width: {{ max(0, min(100, (float) ($percentage ?? 0))) }}%"></span></div>
                </div>
            @endforeach
            <p>Hanya data terverifikasi. Sisa pagu berbeda dengan saldo dana.</p>
        </div>
    </section>
</x-filament-widgets::widget>
