<?php

namespace Tests\Feature;

use App\Data\MonitoringFilter;
use App\Models\{PaguBos, PerubahanPagu, TahapPenyaluran, PenyaluranBos, PenerimaanBos, KomponenBelanja, Rkas, RkasDetail, RealisasiBos, SaldoAwalBos, PenyesuaianSaldo, AuditLog, User};
use App\Services\{Money, MonitoringService, FinancialWorkflow};
use Illuminate\Validation\ValidationException;
use Tests\BosTestCase;

class BosWorkflowTest extends BosTestCase
{
    public function test_exact_balance_draft_exclusion_filters_and_pagu_revision(): void
    {
        $pagu = $this->pagu('1000.10');
        $stage = TahapPenyaluran::create(['tahun_anggaran_id' => $pagu->tahun_anggaran_id, 'jenis_bos_id' => $pagu->jenis_bos_id, 'kode' => 'T1', 'nama' => 'Tahap Uji', 'urutan' => 1]);
        $sent = $this->approve(PenyaluranBos::create(['pagu_bos_id' => $pagu->id, 'tahap_penyaluran_id' => $stage->id, 'nominal' => '800.10', 'tanggal' => '2026-02-01', 'referensi' => 'TEST-SENT', 'sumber' => 'Uji']));
        $this->approve(PenerimaanBos::create(['pagu_bos_id' => $pagu->id, 'penyaluran_bos_id' => $sent->id, 'nominal' => '600.05', 'tanggal' => '2026-02-02', 'referensi' => 'TEST-RECEIPT', 'sumber' => 'Uji']));
        PenerimaanBos::create(['pagu_bos_id' => $pagu->id, 'nominal' => '99.99', 'tanggal' => '2026-02-03', 'referensi' => 'TEST-DRAFT', 'sumber' => 'Uji']);
        $rkas = Rkas::create(['pagu_bos_id' => $pagu->id, 'versi' => 1, 'tanggal' => '2026-01-01', 'sumber' => 'Uji']);
        $component = KomponenBelanja::create(['tahun_anggaran_id' => $pagu->tahun_anggaran_id, 'kode' => 'TEST', 'nama' => 'Komponen Uji', 'sumber' => 'Uji', 'aktif' => true]);
        $detail = RkasDetail::create(['rkas_id' => $rkas->id, 'komponen_belanja_id' => $component->id, 'nominal' => '900.00']);
        $this->approve($rkas);
        $this->approve(RealisasiBos::create(['pagu_bos_id' => $pagu->id, 'rkas_detail_id' => $detail->id, 'mode' => 'transaksi', 'periode_awal' => '2026-03-01', 'periode_akhir' => '2026-03-01', 'tanggal' => '2026-03-01', 'nominal' => '200.02', 'referensi' => 'TEST-SPENT', 'sumber' => 'Uji']));
        $this->approve(SaldoAwalBos::create(['pagu_bos_id' => $pagu->id, 'nominal' => '50.01', 'tanggal' => '2026-01-01', 'referensi' => 'TEST-OPEN', 'sumber' => 'Uji']));
        $this->approve(PenyesuaianSaldo::create(['pagu_bos_id' => $pagu->id, 'nominal' => '-10.01', 'tanggal' => '2026-03-02', 'referensi' => 'TEST-ADJUST', 'sumber' => 'Uji', 'catatan' => 'Koreksi uji']));
        $service = app(MonitoringService::class);
        $result = $service->summary(new MonitoringFilter(['sekolah_id' => $pagu->sekolah_id]));
        $this->assertSame('440.03', $result['saldo']);
        $this->assertSame('400.05', $result['belum_diterima']);
        $this->assertSame('200.05', $result['selisih']);
        $this->assertSame('600.05', $result['penerimaan']);
        $period = $service->summary(new MonitoringFilter(['sekolah_id' => $pagu->sekolah_id, 'mulai' => '2026-03-01', 'sampai' => '2026-03-31']));
        $this->assertSame('440.03', $period['saldo']);
        $this->assertSame('0', $period['penerimaan']);
        $this->assertNull($service->summary(new MonitoringFilter(['tahap_penyaluran_id' => $stage->id]))['saldo']);
        $revision = PerubahanPagu::create(['pagu_bos_id' => $pagu->id, 'nilai_baru' => '1200.10', 'tanggal' => '2026-04-01', 'sumber' => 'Uji', 'alasan' => 'Penetapan baru uji']);
        $this->approve($revision);
        $this->assertSame('1200.10', $pagu->fresh()->pagu_berlaku);
        $this->assertSame('1000.10', $pagu->fresh()->pagu_awal);
        $this->assertSame('1000.10', $service->summary(new MonitoringFilter(['sekolah_id' => $pagu->sekolah_id, 'sampai' => '2026-03-31']))['pagu']);
        foreach (['pagu', 'tahap', 'rkas', 'penerimaan-trend', 'realisasi-trend', 'kecamatan', 'komponen', 'verifikasi'] as $chart) {
            $this->assertNotEmpty(app(\App\Services\ChartData::class)->get($chart, new MonitoringFilter())['labels']);
        }
        $this->assertNotEmpty($service->findings(new MonitoringFilter()));
        $this->assertGreaterThan(0, AuditLog::where('target_id', $pagu->id)->where('target_type', PaguBos::class)->count());
        $this->expectException(ValidationException::class);
        $pagu->refresh()->update(['pagu_awal' => '1.00']);
    }

    public function test_duplicates_and_mixed_recap_are_rejected(): void
    {
        $pagu = $this->pagu();
        $values = ['pagu_bos_id' => $pagu->id, 'nominal' => '10.00', 'tanggal' => '2026-02-01', 'referensi' => 'DUPLICATE', 'sumber' => 'Uji'];
        PenerimaanBos::create($values);
        try { PenerimaanBos::create($values); $this->fail('Duplicate accepted'); } catch (ValidationException $e) { $this->assertArrayHasKey('referensi', $e->errors()); }
        $rkas = Rkas::create(['pagu_bos_id' => $pagu->id, 'versi' => 1, 'tanggal' => '2026-01-01', 'sumber' => 'Uji']);
        $component = KomponenBelanja::create(['tahun_anggaran_id' => $pagu->tahun_anggaran_id, 'kode' => 'TEST', 'nama' => 'Uji', 'sumber' => 'Uji', 'aktif' => true]);
        $detail = RkasDetail::create(['rkas_id' => $rkas->id, 'komponen_belanja_id' => $component->id, 'nominal' => '900.00']); $this->approve($rkas);
        RealisasiBos::create(['pagu_bos_id' => $pagu->id, 'rkas_detail_id' => $detail->id, 'mode' => 'rekap', 'periode_awal' => '2026-02-01', 'periode_akhir' => '2026-02-28', 'tanggal' => '2026-02-28', 'nominal' => '50.00', 'referensi' => 'RECAP', 'sumber' => 'Uji']);
        $this->expectException(ValidationException::class);
        RealisasiBos::create(['pagu_bos_id' => $pagu->id, 'rkas_detail_id' => $detail->id, 'mode' => 'transaksi', 'periode_awal' => '2026-02-10', 'periode_akhir' => '2026-02-10', 'tanggal' => '2026-02-10', 'nominal' => '20.00', 'referensi' => 'DETAIL', 'sumber' => 'Uji']);
    }

    public function test_money_preserves_large_amounts_and_handles_zero_denominator(): void
    {
        $this->assertSame('9999999999999999.99', Money::add('9999999999999999.98', '0.01'));
        $this->assertNull(Money::percent('10', '0'));
        $this->assertSame('33.33', Money::percent('1', '3'));
        $this->assertSame('Rp 1.234.567,89', Money::idr('1234567.89'));
    }
}
