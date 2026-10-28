<x-filament-panels::page>
    {{ $this->form }}
    <x-filament::section heading="Saldo Terhitung" description="Saldo awal + penerimaan − realisasi + penyesuaian sah, kumulatif hingga akhir periode. Sisa pagu merupakan nilai yang berbeda. Saldo tidak dapat diatribusikan ke tahap penyaluran.">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead><tr class="border-b dark:border-gray-700"><th class="p-3">Sekolah / Tahun / Jenis</th><th class="p-3">Pagu Berlaku</th><th class="p-3">Sisa Pagu Belum Diterima</th><th class="p-3">Saldo Dana</th></tr></thead>
            <tbody>@forelse ($this->rows() as $row)
                <tr class="border-b dark:border-gray-700"><td class="p-3">{{ $row['sekolah'] }} · {{ $row['tahun'] }} · {{ $row['jenis'] }}</td><td class="p-3">{{ \App\Services\Money::idr($row['pagu']) }}</td><td class="p-3">{{ \App\Services\Money::idr($row['belum_diterima']) }}</td><td class="p-3">{{ $row['saldo'] === null ? 'Tidak tersedia per tahap' : \App\Services\Money::idr($row['saldo']) }}</td></tr>
            @empty<tr><td colspan="4" class="p-6 text-center">Belum ada pagu terverifikasi sesuai filter.</td></tr>@endforelse</tbody>
        </table></div>
    </x-filament::section>
</x-filament-panels::page>
