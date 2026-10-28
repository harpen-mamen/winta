<x-filament-panels::page>
    {{ $this->form }}
    <x-filament::section heading="Data Menunggu Pemeriksaan" description="Buka modul untuk memeriksa rincian dan dokumen, kemudian setujui atau minta perbaikan. Penginput tidak dapat memverifikasi datanya sendiri.">
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead><tr class="border-b dark:border-gray-700"><th class="p-3">Modul</th><th class="p-3">Data</th><th class="p-3">Penginput</th><th class="p-3">Dokumen</th><th class="p-3">Aksi</th></tr></thead>
            <tbody>@forelse ($this->queue() as $row)<tr class="border-b dark:border-gray-700"><td class="p-3">{{ $row['modul'] }}</td><td class="p-3">{{ $row['label'] }}</td><td class="p-3">{{ $row['penginput'] }}</td><td class="p-3">{{ $row['dokumen'] }}</td><td class="p-3"><x-filament::link :href="$row['url']">Periksa Modul</x-filament::link></td></tr>
            @empty<tr><td colspan="5" class="p-6 text-center">Tidak ada pengajuan yang menunggu verifikasi.</td></tr>@endforelse</tbody>
        </table></div>
    </x-filament::section>
</x-filament-panels::page>
