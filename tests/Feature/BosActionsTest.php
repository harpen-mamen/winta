<?php

namespace Tests\Feature;

use App\Filament\Resources\{SekolahResource\Pages\ManageSekolah, PaguBosResource\Pages\ManagePaguBos, UserResource\Pages\ManageUsers};
use App\Models\{Kecamatan, Sekolah, PaguBos, TahunAnggaran, JenisBos, User};
use Filament\Facades\Filament;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\BosTestCase;

class BosActionsTest extends BosTestCase
{
    public function test_native_school_create_edit_search_and_duplicate_validation(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $district = Kecamatan::create(['kode' => 'TEST', 'nama' => 'Kecamatan Uji', 'aktif' => true]);
        $data = ['npsn' => '12345678', 'nama' => 'SEKOLAH UJI', 'status_sekolah' => 'Negeri', 'kecamatan_id' => (string) $district->id, 'alamat' => 'Alamat uji', 'aktif' => true, 'is_demo' => true];
        Livewire::test(ManageSekolah::class)->callAction('create', data: $data)->assertHasNoFormErrors();
        $school = Sekolah::where('npsn', '12345678')->firstOrFail();
        Livewire::test(ManageSekolah::class)->callAction(TestAction::make('edit')->table($school), data: array_merge($data, ['nama' => 'SEKOLAH UJI DIUBAH']))->assertHasNoFormErrors();
        $this->assertSame('SEKOLAH UJI DIUBAH', $school->fresh()->nama);
        Livewire::test(ManageSekolah::class)->callAction('create', data: $data)->assertHasFormErrors(['npsn' => 'unique']);
        Livewire::test(ManageSekolah::class)->searchTable('DIUBAH')->assertCanSeeTableRecords([$school]);
    }
    public function test_pagu_can_be_created_with_browser_string_ids(): void
    {
        $existing = $this->pagu();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $school = Sekolah::create(['npsn' => '87654321', 'nama' => 'SEKOLAH UJI 2', 'status_sekolah' => 'Swasta', 'kecamatan_id' => $existing->sekolah->kecamatan_id, 'aktif' => true, 'is_demo' => true]);
        Livewire::test(ManagePaguBos::class)->callAction('create', data: ['sekolah_id' => (string) $school->id, 'tahun_anggaran_id' => (string) $existing->tahun_anggaran_id, 'jenis_bos_id' => (string) $existing->jenis_bos_id, 'pagu_awal' => '123456.78', 'tanggal' => '2026-01-01', 'nomor_dokumen' => 'TEST-UI', 'sumber' => 'Uji', 'catatan' => null])->assertHasNoFormErrors();
        $record = PaguBos::where('sekolah_id', $school->id)->firstOrFail();
        $this->assertSame('123456.78', $record->pagu_berlaku); $this->assertSame('draft', $record->status);
    }
    public function test_admin_can_create_verifier_without_exposing_password_in_audit(): void
    {
        $this->actingAs($this->admin); Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageUsers::class)->callAction('create', data: ['name' => 'Verifikator Uji', 'email' => 'verifier-test@example.test', 'password' => 'Test-Only-Long-Password-239!', 'role_ids' => [Role::findByName('Verifikator')->id], 'aktif' => true])->assertHasNoFormErrors();
        $user = User::where('email', 'verifier-test@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('Verifikator'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Test-Only-Long-Password-239!', $user->password));
        $this->assertStringNotContainsString('Test-Only-Long', (string) \App\Models\AuditLog::where('aksi', 'kelola_pengguna')->first()->sesudah['email']);
    }
}
