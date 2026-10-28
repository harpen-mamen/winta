<x-filament-panels::page>
    {{ $this->form }}
    @php($report = $this->report())
    <div class="flex flex-wrap gap-3">
    @can('reports.export')
        <x-filament::button tag="a" :href="$this->downloadUrl('xlsx')" icon="heroicon-o-table-cells">Ekspor Excel</x-filament::button>
        <x-filament::button tag="a" :href="$this->downloadUrl('pdf')" color="gray" icon="heroicon-o-document-arrow-down">Ekspor PDF</x-filament::button>
    @endcan
    </div>
    <x-filament::section :heading="$report['title']" :description="$report['source']">
        <p class="mb-4 text-sm">Dibuat: {{ $report['created_at'] }} · Pembuat: {{ $report['creator'] }}</p>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm">
            <thead><tr class="border-b dark:border-gray-700">@foreach($report['columns'] as $column)<th class="p-3 whitespace-nowrap">{{ $column }}</th>@endforeach</tr></thead>
            <tbody>@forelse($report['rows'] as $row)<tr class="border-b dark:border-gray-700">@foreach($row as $value)<td class="p-3">{{ $value }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($report['columns']) }}" class="p-6 text-center">Belum ada data terverifikasi sesuai filter laporan.</td></tr>@endforelse</tbody>
        </table></div>
    </x-filament::section>
</x-filament-panels::page>
