<?php

namespace Database\Seeders;

use App\Data\MonitoringFilter;
use App\Models\{User, Kecamatan, Sekolah, DataMurid, TahunAnggaran, JenisBos, KomponenBelanja, TahapPenyaluran, PaguBos, PerubahanPagu, PenyaluranBos, PenerimaanBos, Rkas, RkasDetail, RealisasiBos, SaldoAwalBos, PenyesuaianSaldo, SaldoDilaporkan, FinancialRecord, DokumenPendukung, Evaluasi};
use App\Services\{FinancialWorkflow, MonitoringService};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{Auth, DB, Storage};
use Illuminate\Support\Str;

class DemoBosSeeder extends Seeder
{
    private const SOURCE = 'DATA DEMO - SIMULASI FIKTIF, BUKAN DATA RESMI';
    private User $operator;
    private User $verifier;
    private string $documentPath = 'demo/bukti-simulasi-bos-2026.pdf';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) throw new \RuntimeException('Seeder demo hanya diizinkan pada lingkungan local/testing.');
        $previous = Auth::user();
        try {
            DB::transaction(function () {
                $this->operator = $this->actor('operator', 'Operator BOS');
                $this->verifier = $this->actor('verifikator', 'Verifikator');
                $this->asOperator();
                $year = TahunAnggaran::firstOrCreate(['tahun' => 2026], ['aktif' => true, 'dikunci' => false]);
                if ($year->dikunci) throw new \RuntimeException('Tahun 2026 terkunci. Data demo tidak mengubah penguncian tahun.');
                $type = JenisBos::firstOrCreate(['kode' => 'DEMO-BOS'], ['nama' => '[DEMO] BOS Simulasi', 'sumber' => self::SOURCE, 'aktif' => true]);
                $districts = [];
                foreach (['Pesisir', 'Kepulauan', 'Perbukitan'] as $i => $name) $districts[] = Kecamatan::firstOrCreate(['kode' => 'DEMO-KEC-'.($i + 1)], ['nama' => '[DEMO] Kecamatan '.$name, 'aktif' => true]);
                $components = [];
                foreach (['Kegiatan Pembelajaran', 'Perpustakaan', 'Pemeliharaan Sarana', 'Administrasi Sekolah'] as $i => $name) $components[] = KomponenBelanja::firstOrCreate(['tahun_anggaran_id' => $year->id, 'kode' => 'DEMO-K'.($i + 1)], ['nama' => '[DEMO] '.$name, 'sumber' => self::SOURCE, 'aktif' => true]);
                $stages = [];
                foreach ([['DEMO-T1', '[DEMO] Tahap 1', '2026-02-10'], ['DEMO-T2', '[DEMO] Tahap 2', '2026-08-10']] as $i => [$code, $name, $date]) $stages[] = TahapPenyaluran::firstOrCreate(['tahun_anggaran_id' => $year->id, 'jenis_bos_id' => $type->id, 'kode' => $code], ['nama' => $name, 'urutan' => $i + 1, 'jadwal' => $date]);
                if (! Storage::disk('local')->exists($this->documentPath)) Storage::disk('local')->put($this->documentPath, Pdf::loadHTML('<h1>DATA DEMO - BUKAN DOKUMEN RESMI</h1><p>Dokumen simulasi untuk mencoba sistem Monitoring BOS SMP Sangihe. Semua sekolah, transaksi, dan nilai anggaran pada dataset DEMO merupakan data fiktif. Tidak memiliki kekuatan administrasi atau bukti penyaluran dana.</p>')->setOption('isRemoteEnabled', false)->output());
                $names = ['Bahari', 'Cakrawala', 'Anggrek', 'Pelita', 'Samudra', 'Mentari', 'Tunas', 'Harapan', 'Nusantara', 'Bintang'];
                $budgets = ['240000000.00', '180000000.00', '300000000.00', '210000000.00', '150000000.00', '270000000.00', '195000000.00', '225000000.00', '165000000.00', '255000000.00'];
                foreach ($names as $i => $name) {
                    $number = $i + 1; $prefix = 'DEMO-2026-S'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
                    $npsn = '9900'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
                    $school = Sekolah::firstOrCreate(['npsn' => $npsn], ['nama' => '[DEMO] SMP '.$name, 'status_sekolah' => $i % 3 === 0 ? 'Swasta' : 'Negeri', 'kecamatan_id' => $districts[$i % 3]->id, 'alamat' => 'Alamat simulasi '.$number.' - bukan lokasi sekolah sebenarnya', 'aktif' => true, 'is_demo' => true]);
                    if (! $school->is_demo || ! str_starts_with($school->nama, '[DEMO]')) throw new \RuntimeException('NPSN demo berbenturan dengan data lain. Tidak ada data lama yang diubah.');
                    foreach ([2025 => 110 + $number * 13, 2026 => 120 + $number * 14] as $surveyYear => $students) DataMurid::firstOrCreate(['sekolah_id' => $school->id, 'tahun_pendataan' => $surveyYear], ['jumlah' => $students, 'sumber' => self::SOURCE, 'tanggal' => $surveyYear.'-01-10']);
                    $pagu = PaguBos::firstOrCreate(['sekolah_id' => $school->id, 'tahun_anggaran_id' => $year->id, 'jenis_bos_id' => $type->id], ['pagu_awal' => $budgets[$i], 'tanggal' => '2026-01-15', 'nomor_dokumen' => $prefix.'-PAGU', 'sumber' => self::SOURCE, 'catatan' => self::SOURCE]);
                    if ($pagu->wasRecentlyCreated) $this->finish($pagu, $i === 8 ? 'diajukan' : ($i === 9 ? 'draft' : 'terverifikasi'));
                    if ($i >= 8) continue;
                    if ($pagu->status !== 'terverifikasi') continue;
                    foreach ($stages as $stageIndex => $stage) {
                        $amount = bcmul($budgets[$i], $stageIndex === 0 ? '0.50' : '0.35', 2);
                        $date = $stageIndex === 0 ? '2026-02-10' : '2026-08-10';
                        $distribution = $this->transaction(PenyaluranBos::class, $prefix.'-SALUR-'.($stageIndex + 1), ['pagu_bos_id' => $pagu->id, 'tahap_penyaluran_id' => $stage->id, 'nominal' => $amount, 'tanggal' => $date]);
                        if ($stageIndex === 1 && $i === 3) continue;
                        $received = $stageIndex === 0 && $i === 2 ? bcmul($amount, '0.80', 2) : $amount;
                        $status = $stageIndex === 1 ? match ($i) { 5 => 'perlu_perbaikan', 6 => 'draft', 7 => 'diajukan', default => 'terverifikasi' } : 'terverifikasi';
                        $this->transaction(PenerimaanBos::class, $prefix.'-TERIMA-'.($stageIndex + 1), ['pagu_bos_id' => $pagu->id, 'penyaluran_bos_id' => $distribution->id, 'nominal' => $received, 'tanggal' => $stageIndex === 0 ? '2026-02-12' : '2026-08-12'], $status);
                    }
                    $rkas = Rkas::firstOrCreate(['pagu_bos_id' => $pagu->id, 'versi' => 1], ['tanggal' => '2026-01-20', 'sumber' => self::SOURCE, 'catatan' => self::SOURCE]);
                    $newRkas = $rkas->wasRecentlyCreated; $details = [];
                    foreach (['0.40', '0.25', '0.20', '0.15'] as $componentIndex => $weight) $details[] = RkasDetail::firstOrCreate(['rkas_id' => $rkas->id, 'komponen_belanja_id' => $components[$componentIndex]->id], ['nominal' => bcmul($budgets[$i], $weight, 2)]);
                    if ($newRkas) $this->finish($rkas);
                    foreach (['2026-02-20', '2026-04-20', '2026-06-20', '2026-08-20', '2026-09-20', '2026-10-20'] as $monthIndex => $date) {
                        foreach ($details as $componentIndex => $detail) {
                            $rate = ['0.06', '0.08', '0.10', '0.07', '0.09', '0.08'][$monthIndex];
                            $status = $i === 1 && $monthIndex === 5 && $componentIndex === 3 ? 'draft' : 'terverifikasi';
                            $this->transaction(RealisasiBos::class, $prefix.'-BELANJA-'.($monthIndex + 1).'-'.($componentIndex + 1), ['pagu_bos_id' => $pagu->id, 'rkas_detail_id' => $detail->id, 'mode' => 'transaksi', 'periode_awal' => $date, 'periode_akhir' => $date, 'tanggal' => $date, 'nominal' => bcmul($detail->nominal, $rate, 2)], $status);
                        }
                    }
                    $this->transaction(SaldoAwalBos::class, $prefix.'-SALDO-AWAL', ['pagu_bos_id' => $pagu->id, 'nominal' => (string) (1000000 + $i * 250000).'.00', 'tanggal' => '2026-01-01']);
                    if ($i === 0) {
                        $this->transaction(PenyesuaianSaldo::class, $prefix.'-PENYESUAIAN', ['pagu_bos_id' => $pagu->id, 'nominal' => '-125000.00', 'tanggal' => '2026-09-01']);
                        $revision = PerubahanPagu::firstOrCreate(['pagu_bos_id' => $pagu->id, 'sumber' => self::SOURCE], ['nilai_baru' => bcadd($budgets[$i], '12000000.00', 2), 'tanggal' => '2026-10-01', 'alasan' => '[DEMO] Contoh riwayat perubahan pagu simulasi', 'catatan' => self::SOURCE]);
                        if ($revision->wasRecentlyCreated) $this->finish($revision);
                    }
                    $filter = new MonitoringFilter(['tahun_anggaran_id' => $year->id, 'jenis_bos_id' => $type->id, 'sekolah_id' => $school->id, 'sampai' => '2026-10-25']);
                    $balance = app(MonitoringService::class)->summary($filter)['saldo'];
                    $this->transaction(SaldoDilaporkan::class, $prefix.'-SALDO-LAPORAN', ['pagu_bos_id' => $pagu->id, 'nominal' => $i === 2 ? bcadd($balance, '150000.00', 2) : $balance, 'tanggal' => '2026-10-25']);
                }
                $this->asOperator();
            });
        } finally {
            if ($previous) Auth::guard('web')->setUser($previous); else Auth::guard('web')->forgetUser();
        }
        $this->command?->info('Data DEMO 2026 siap: 10 sekolah fiktif. Semua referensi dan transaksi berlabel DEMO; data lama dipertahankan.');
    }

    private function actor(string $key, string $role): User
    {
        $user = User::firstOrCreate(['email' => 'demo-'.$key.'@bos-smp.test'], ['name' => '[DEMO] '.$role, 'password' => Str::random(48)]);
        if (! str_starts_with($user->name, '[DEMO]')) throw new \RuntimeException('Akun demo berbenturan dengan pengguna lain.');
        $user->assignRole($role);
        return $user;
    }
    private function asOperator(): void { Auth::guard('web')->setUser($this->operator); }
    private function transaction(string $class, string $reference, array $attributes, string $status = 'terverifikasi'): FinancialRecord
    {
        $this->asOperator();
        $record = $class::firstOrCreate(['referensi' => $reference], $attributes + ['sumber' => self::SOURCE, 'catatan' => self::SOURCE]);
        if ($record->wasRecentlyCreated) $this->finish($record, $status);
        return $record;
    }
    private function finish(FinancialRecord $record, string $status = 'terverifikasi'): void
    {
        if ($status === 'draft') return;
        $this->asOperator();
        DokumenPendukung::firstOrCreate(['target_type' => $record->getMorphClass(), 'target_id' => $record->id, 'path' => $this->documentPath], ['nama' => 'DEMO - Bukti simulasi, bukan dokumen resmi.pdf', 'mime' => 'application/pdf', 'ukuran' => Storage::disk('local')->size($this->documentPath), 'checksum' => hash_file('sha256', Storage::disk('local')->path($this->documentPath)), 'uploaded_by' => $this->operator->id]);
        app(FinancialWorkflow::class)->transition($record, 'diajukan', '[DEMO] Pengajuan simulasi');
        if ($status !== 'diajukan') {
            Auth::guard('web')->setUser($this->verifier);
            app(FinancialWorkflow::class)->transition($record, $status, '[DEMO] '.($status === 'perlu_perbaikan' ? 'Contoh permintaan perbaikan bukti simulasi.' : 'Verifikasi simulasi untuk demonstrasi aplikasi, bukan pemeriksaan dokumen resmi.'));
        }
        $this->asOperator();
    }
}
