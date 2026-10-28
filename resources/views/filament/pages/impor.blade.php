<x-filament-panels::page>
    <x-filament::section heading="Template dan Petunjuk Impor" description="XLSX maksimal 5 MB dan 1.000 baris per batch. Pratinjau divalidasi tanpa menyimpan data. Seluruh batch disimpan dalam satu transaksi setelah Anda menekan Simpan Batch.">
        <div class="space-y-3 text-sm">
            <p>Gunakan template dengan header persis. Kolom berakhiran _id menggunakan ID master/pagu yang tersedia pada modul terkait. Atur kolom NPSN dan nilai uang sebagai Text. Tanggal menggunakan YYYY-MM-DD. Aktif/penanda demo menggunakan 1 atau 0. Data keuangan yang diimpor tetap draft.</p>
            <div class="flex flex-wrap gap-3">@foreach(\App\Services\ImportService::modules() as $key => $label)<x-filament::link :href="route('import.template', ['module' => $key])">Template {{ $label }}</x-filament::link>@endforeach</div>
        </div>
    </x-filament::section>
    @if($preview = $this->preview)
    <x-filament::section heading="Hasil Validasi">
        <p>{{ $preview['jumlah'] }} baris diperiksa. {{ count($preview['errors']) }} baris perlu diperbaiki. Pratinjau maksimal 10 baris.</p>
        @foreach($preview['errors'] as $line => $error)<p class="mt-2 text-danger-600">Baris {{ $line }}: {{ $error }}</p>@endforeach
        <div class="mt-4 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>@foreach(array_keys($preview['rows'][0] ?? []) as $column)<th class="p-2">{{ $column }}</th>@endforeach</tr></thead><tbody>@foreach($preview['rows'] as $row)<tr>@foreach($row as $value)<td class="border-t p-2">{{ is_bool($value) ? (int) $value : $value }}</td>@endforeach</tr>@endforeach</tbody></table></div>
    </x-filament::section>
    @endif
</x-filament-panels::page>
