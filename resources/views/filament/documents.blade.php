<div class="space-y-3">
    @forelse ($record->documents as $document)
        <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <span>{{ $document->nama }}</span>
            <x-filament::link :href="route('documents.download', $document)" target="_blank">Unduh</x-filament::link>
        </div>
    @empty
        <p>Belum ada dokumen pendukung. Unggah bukti sebelum mengajukan verifikasi.</p>
    @endforelse
</div>
