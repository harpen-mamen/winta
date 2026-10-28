<?php

namespace Tests\Feature;

use App\Models\{Sekolah, Kecamatan, ImportBatch, DokumenPendukung, User};
use App\Services\{ImportService, DocumentService};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use Tests\BosTestCase;

class BosTransferTest extends BosTestCase
{
    private function spreadsheet(array $rows, string $module = 'sekolahs'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bos-test-');
        $writer = new Writer(); $writer->openToFile($path);
        $writer->addRow(Row::fromValues(array_keys(config('bos-modules.'.$module.'.fields'))));
        foreach ($rows as $row) $writer->addRow(Row::fromValues($row)); $writer->close();
        $this->beforeApplicationDestroyed(fn () => is_file($path) ? unlink($path) : null);
        return new UploadedFile($path, 'sekolah-uji.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
    public function test_xlsx_preview_is_rolled_back_and_commit_is_idempotent(): void
    {
        Storage::fake('local');
        $district = Kecamatan::create(['kode' => 'TEST', 'nama' => 'Kecamatan Uji', 'aktif' => true]);
        $file = $this->spreadsheet([['12345678', 'SEKOLAH UJI', 'Negeri', $district->id, 'Alamat uji', true, true]]);
        $service = app(ImportService::class); $preview = $service->preview('sekolahs', $file);
        $this->assertSame([], $preview['errors']); $this->assertSame(0, Sekolah::count());
        $this->assertSame(1, $service->commit($preview['token']));
        $this->assertSame(1, Sekolah::count()); $this->assertSame(1, ImportBatch::where('status', 'berhasil')->count());
        $this->expectException(ValidationException::class); $service->preview('sekolahs', $file);
    }
    public function test_invalid_batch_keeps_all_rows_out_of_database(): void
    {
        Storage::fake('local'); $district = Kecamatan::create(['kode' => 'TEST', 'nama' => 'Uji', 'aktif' => true]);
        $row = ['12345678', 'SEKOLAH UJI', 'Negeri', $district->id, null, true, true];
        $preview = app(ImportService::class)->preview('sekolahs', $this->spreadsheet([$row, $row]));
        $this->assertArrayHasKey(3, $preview['errors']); $this->assertSame(0, Sekolah::count());
        $this->expectException(ValidationException::class); app(ImportService::class)->commit($preview['token']);
    }
    public function test_financial_import_stays_draft_and_duplicate_references_are_rejected(): void
    {
        Storage::fake('local'); $pagu = $this->pagu();
        $data = ['pagu_bos_id' => (string) $pagu->id, 'penyaluran_bos_id' => null, 'nominal' => '123.45', 'tanggal' => '2026-03-01', 'referensi' => 'XLSX-FINANCE', 'sumber' => 'UJI', 'catatan' => null, 'koreksi_atas_id' => null];
        $rows = [array_map(fn ($key) => $data[$key], array_keys(config('bos-modules.penerimaan_bos.fields')))];
        $service = app(ImportService::class); $preview = $service->preview('penerimaan_bos', $this->spreadsheet($rows, 'penerimaan_bos'));
        $this->assertSame([], $preview['errors']); $service->commit($preview['token']);
        $this->assertSame('draft', \App\Models\PenerimaanBos::first()->status);
        $this->assertSame('0', app(\App\Services\MonitoringService::class)->summary(new \App\Data\MonitoringFilter())['penerimaan']);
        $data['sumber'] = 'UJI DUPLIKAT';
        $rows = [array_map(fn ($key) => $data[$key], array_keys(config('bos-modules.penerimaan_bos.fields')))];
        $duplicate = $service->preview('penerimaan_bos', $this->spreadsheet($rows, 'penerimaan_bos'));
        $this->assertArrayHasKey(2, $duplicate['errors']);
    }
    public function test_private_documents_require_authorization_and_valid_mime(): void
    {
        Storage::fake('local'); $pagu = $this->pagu();
        $draft = \App\Models\PenerimaanBos::create(['pagu_bos_id' => $pagu->id, 'nominal' => '20.00', 'tanggal' => '2026-02-01', 'referensi' => 'DOC-TEST', 'sumber' => 'Uji']);
        $document = app(DocumentService::class)->store($draft, UploadedFile::fake()->create('bukti.pdf', 5, 'application/pdf'));
        Storage::disk('local')->assertExists($document->path);
        $this->get(route('documents.download', $document))->assertOk();
        $pimpinan = User::factory()->create()->assignRole('Pimpinan Dinas');
        $this->actingAs($pimpinan)->get(route('documents.download', $document))->assertForbidden();
        $this->actingAs($this->operator);
        $this->expectException(ValidationException::class);
        app(DocumentService::class)->store($draft, UploadedFile::fake()->create('payload.php', 1, 'text/x-php'));
    }
    public function test_excel_pdf_and_templates_download_with_metadata(): void
    {
        $this->pagu();
        $xlsx = $this->get('/laporan/xlsx?jenis=pagu'); $xlsx->assertOk(); $this->assertStringStartsWith('PK', $xlsx->streamedContent());
        $pdf = $this->get('/laporan/pdf?jenis=pagu'); $pdf->assertOk(); $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $template = $this->get('/template/sekolahs'); $template->assertOk(); $this->assertStringStartsWith('PK', $template->streamedContent());
        $this->get('/ekspor/pagu_bos/xlsx')->assertOk();
        $this->get('/laporan/xlsx?jenis=tidak-ada')->assertNotFound();
    }
}
