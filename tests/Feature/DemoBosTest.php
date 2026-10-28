<?php

namespace Tests\Feature;

use App\Data\MonitoringFilter;
use App\Models\{Sekolah, PaguBos, PenyaluranBos, PenerimaanBos, RealisasiBos};
use App\Services\{MonitoringService, ReportService};
use Database\Seeders\DemoBosSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\BosTestCase;

class DemoBosTest extends BosTestCase
{
    public function test_demo_is_labelled_idempotent_and_isolated_from_official_data(): void
    {
        Storage::fake('local');
        $original = $this->pagu('1000.00'); $original->sekolah->update(['is_demo' => false]);
        $this->seed(DemoBosSeeder::class);
        $models = [Sekolah::class, PaguBos::class, PenyaluranBos::class, PenerimaanBos::class, RealisasiBos::class];
        $counts = array_map(fn ($model) => $model::count(), $models);
        $this->seed(DemoBosSeeder::class);
        $this->assertSame($counts, array_map(fn ($model) => $model::count(), $models));
        $this->assertSame(10, Sekolah::where('is_demo', true)->count());
        $this->assertSame(0, Sekolah::where('is_demo', true)->where('nama', 'not like', '[DEMO]%')->count());
        $service = app(MonitoringService::class);
        $official = $service->summary(new MonitoringFilter(['lingkup' => 'resmi']));
        $demo = $service->summary(new MonitoringFilter(['lingkup' => 'demo']));
        $this->assertSame('1000.00', $official['pagu']); $this->assertSame(1, $official['jumlah_sekolah']);
        $this->assertSame(10, $demo['jumlah_sekolah']); $this->assertSame('1782000000.00', $demo['pagu']);
        $this->assertSame('1000.00', $original->fresh()->pagu_berlaku);
        $this->assertGreaterThan(0, RealisasiBos::where('status', 'terverifikasi')->count());
        $report = app(ReportService::class)->dataset('pagu', new MonitoringFilter(['lingkup' => 'demo']));
        $this->assertTrue($report['is_demo']); $this->assertStringContainsString('BUKAN LAPORAN RESMI', $report['source']);
        $officialReport = app(ReportService::class)->dataset('pagu', new MonitoringFilter(['lingkup' => 'resmi']));
        $this->assertFalse($officialReport['is_demo']); $this->assertCount(1, $officialReport['rows']);
        $moduleReport = app(ReportService::class)->module('pagu_bos', ['lingkup' => ['value' => 'resmi']]);
        $this->assertCount(1, $moduleReport['rows']); $this->assertFalse($moduleReport['is_demo']);
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('DATA DEMO');
    }
}
