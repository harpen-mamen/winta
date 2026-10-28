@if(\App\Models\Sekolah::where('is_demo', true)->exists())
<div class="bos-demo-banner" role="note">
    <span class="bos-demo-badge">DATA DEMO</span>
    <span>Data berlabel <strong>[DEMO]</strong> merupakan simulasi fiktif, bukan data resmi. Gunakan filter <strong>Lingkup Data</strong> untuk memisahkannya.</span>
</div>
@endif
