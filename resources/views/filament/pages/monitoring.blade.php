<x-filament-panels::page>
    {{ $this->form }}
    <x-filament::section heading="Monitoring dan Evaluasi" description="Indikator ini membantu pemeriksaan dan tidak otomatis menyatakan pelanggaran. Data belum diverifikasi tidak masuk perhitungan resmi.">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr class="border-b dark:border-gray-700"><th class="p-3">Sekolah</th><th class="p-3">Indikator</th><th class="p-3">Catatan</th></tr></thead>
                <tbody>
                @forelse ($this->findings() as $finding)
                    <tr class="border-b dark:border-gray-700"><td class="p-3">{{ $finding['sekolah'] }}</td><td class="p-3"><x-filament::badge color="warning">{{ $finding['indikator'] }}</x-filament::badge></td><td class="p-3">{{ $finding['catatan'] }}</td></tr>
                @empty
                    <tr><td colspan="3" class="p-6 text-center">Belum ada temuan pada filter ini. Jika data belum diinput, lengkapi master dan data keuangan terlebih dahulu.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
