<?php

namespace Tests;

use App\Models\{User, DokumenPendukung, FinancialRecord, Kecamatan, Sekolah, TahunAnggaran, JenisBos, PaguBos};
use App\Services\FinancialWorkflow;
use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class BosTestCase extends TestCase
{
    use DatabaseTransactions;
    protected User $admin;
    protected User $operator;
    protected User $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'monitoring_bos_smp_test') throw new \RuntimeException('Tests require isolated BOS database.');
        $this->seed();
        $this->admin = User::factory()->create()->assignRole('Super Admin');
        $this->operator = User::factory()->create()->assignRole('Operator BOS');
        $this->verifier = User::factory()->create()->assignRole('Verifikator');
        $this->actingAs($this->operator);
    }
    protected function approve(FinancialRecord $record): FinancialRecord
    {
        $creator = User::find($record->created_by);
        $this->actingAs($creator);
        DokumenPendukung::create(['target_type' => $record->getMorphClass(), 'target_id' => $record->id, 'nama' => 'bukti-uji.pdf', 'path' => 'test/bukti.pdf', 'mime' => 'application/pdf', 'ukuran' => 10, 'checksum' => str_repeat('a', 64), 'uploaded_by' => $creator->id]);
        app(FinancialWorkflow::class)->transition($record, 'diajukan');
        $this->actingAs($this->verifier);
        app(FinancialWorkflow::class)->transition($record, 'terverifikasi', 'Dokumen uji terperiksa.');
        $this->actingAs($this->operator);
        return $record->refresh();
    }
    protected function pagu(string $amount = '1000.00'): PaguBos
    {
        $district = Kecamatan::create(['kode' => 'TEST-'.uniqid(), 'nama' => 'KECAMATAN UJI — BUKAN DATA RESMI', 'aktif' => true]);
        $school = Sekolah::create(['npsn' => (string) random_int(10000000, 99999999), 'nama' => 'SEKOLAH UJI — BUKAN DATA RESMI', 'status_sekolah' => 'Negeri', 'kecamatan_id' => $district->id, 'aktif' => true, 'is_demo' => true]);
        return $this->approve(PaguBos::create(['sekolah_id' => $school->id, 'tahun_anggaran_id' => TahunAnggaran::first()->id, 'jenis_bos_id' => JenisBos::first()->id, 'pagu_awal' => $amount, 'tanggal' => '2026-01-01', 'nomor_dokumen' => 'TEST-'.uniqid(), 'sumber' => 'Pengujian otomatis']));
    }
}
