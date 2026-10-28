<?php

namespace Tests\Feature;

use App\Filament\Pages;
use App\Filament\Widgets;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\BosTestCase;

class BosPagesTest extends BosTestCase
{
    public function test_admin_can_open_all_module_pages(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin')->assertOk()->assertSee('Dashboard Monitoring BOS');
        foreach (config('bos-modules') as $module) {
            $resource = 'App\\Filament\\Resources\\'.class_basename($module['model']).'Resource';
            $this->get($resource::getUrl())->assertOk();
        }
        foreach (['laporan', 'monitoring', 'saldo-dana', 'grafik-analitik', 'verifikasi', 'impor-data', 'users', 'roles'] as $page) $this->get('/admin/'.$page)->assertOk();
    }
    public function test_role_boundaries_are_enforced_for_pages_and_exports(): void
    {
        $this->actingAs($this->operator);
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
        $this->get('/admin/audit-logs')->assertForbidden();
        $this->get('/admin/verifikasi')->assertForbidden();
        $this->get('/ekspor/audit_logs/xlsx')->assertForbidden();
        $pimpinan = User::factory()->create()->assignRole('Pimpinan Dinas');
        $this->actingAs($pimpinan);
        $this->get('/admin/impor-data')->assertForbidden();
        $this->get('/admin/verifikasi')->assertForbidden();
        $this->get('/admin/laporan')->assertOk();
    }
    public function test_widgets_handle_empty_database_and_consistent_filters(): void
    {
        $this->actingAs($this->admin); Filament::setCurrentPanel(Filament::getPanel('admin'));
        foreach ([Widgets\BosStats::class, Widgets\PaguChart::class, Widgets\TahapChart::class, Widgets\RkasChart::class, Widgets\PenerimaanTrendChart::class, Widgets\RealisasiTrendChart::class, Widgets\KecamatanChart::class, Widgets\KomponenChart::class, Widgets\VerifikasiChart::class] as $widget) Livewire::test($widget, ['pageFilters' => []])->assertSuccessful();
    }
    public function test_inactive_users_cannot_access_panel_or_exports(): void
    {
        $this->admin->forceFill(['aktif' => false])->save();
        $this->actingAs($this->admin)->get('/admin')->assertForbidden();
        $this->actingAs($this->admin)->get('/laporan/xlsx?jenis=pagu')->assertForbidden();
    }
}
