<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#172033}h1{font-size:16px;margin:4px 0}h2{font-size:12px;font-weight:normal}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{padding:6px;border:1px solid #ccd3df;text-align:left;vertical-align:top}th{background:#eef2f8}footer{margin-top:20px;color:#586578}
</style></head><body>
<h1>MONITORING BOS SMP SANGIHE</h1><h2>Dinas Pendidikan dan Kebudayaan Daerah Kabupaten Kepulauan Sangihe</h2>
@if($report['is_demo'] ?? false)<p style="padding:12px;border:2px solid #b45309;background:#fffbeb;font-weight:bold;color:#92400e">DATA DEMO FIKTIF — BUKAN LAPORAN ATAU DOKUMEN RESMI</p>@endif
<h2>{{ $report['title'] }}</h2><p>Dibuat: {{ $report['created_at'] }} · Pembuat: {{ $report['creator'] }}</p><p>{{ $report['source'] }}</p>
<p>Filter: {{ json_encode($report['filters'] ?? [], JSON_UNESCAPED_UNICODE) }}</p>
<table><thead><tr>@foreach($report['columns'] as $column)<th>{{ $column }}</th>@endforeach</tr></thead><tbody>
@forelse($report['rows'] as $row)<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($report['columns']) }}">Belum ada data sesuai filter.</td></tr>@endforelse
</tbody></table><footer>Rekap monitoring internal. Angka uang dalam Rupiah. Dokumen ini tidak menggantikan dokumen penetapan atau pengesahan resmi instansi.</footer>
</body></html>
