<?php

namespace Tests\Feature;

use App\Models\{PenerimaanBos, AuditLog, User, DokumenPendukung};
use App\Services\{FinancialWorkflow, MonitoringService};
use App\Data\MonitoringFilter;
use Filament\Facades\Filament;
use Filament\Auth\Pages\Login;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\BosTestCase;

class BosSecurityTest extends BosTestCase
{
    public function test_login_logout_and_audit_events(): void
    {
        $this->admin->password = 'Testing-Login-Only-239!'; $this->admin->save();
        auth()->logout(); Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(Login::class)->fillForm(['email' => $this->admin->email, 'password' => 'Testing-Login-Only-239!', 'remember' => false])->call('authenticate')->assertHasNoFormErrors();
        $this->assertAuthenticatedAs($this->admin);
        $this->post('/admin/logout')->assertRedirect('/admin/login'); $this->assertGuest();
        $this->assertTrue(AuditLog::where('user_id', $this->admin->id)->where('aksi', 'login')->exists());
        $this->assertTrue(AuditLog::where('user_id', $this->admin->id)->where('aksi', 'logout')->exists());
    }
    public function test_self_verification_and_status_tampering_are_blocked(): void
    {
        $pagu = $this->pagu(); $this->actingAs($this->admin);
        $record = PenerimaanBos::create(['pagu_bos_id' => (string) $pagu->id, 'nominal' => '20.00', 'tanggal' => '2026-02-01', 'referensi' => 'SELF-VERIFY', 'sumber' => 'Uji']);
        DokumenPendukung::create(['target_type' => get_class($record), 'target_id' => $record->id, 'nama' => 'Uji', 'path' => 'test.pdf', 'mime' => 'application/pdf', 'ukuran' => 1, 'checksum' => str_repeat('a', 64), 'uploaded_by' => $this->admin->id]);
        app(FinancialWorkflow::class)->transition($record, 'diajukan');
        $this->assertFalse(Gate::allows('verify', $record));
        $this->actingAs($this->verifier); $this->assertTrue(Gate::allows('verify', $record));
        $this->expectException(ValidationException::class); $record->forceFill(['status' => 'terverifikasi'])->save();
    }
    public function test_pimpinan_queries_exclude_draft_and_financial_reversal_is_audited(): void
    {
        $pagu = $this->pagu();
        $original = $this->approve(PenerimaanBos::create(['pagu_bos_id' => (string) $pagu->id, 'nominal' => '123.45', 'tanggal' => '2026-02-01', 'referensi' => 'ORIGINAL', 'sumber' => 'Uji']));
        $reversal = PenerimaanBos::create(['pagu_bos_id' => (string) $pagu->id, 'nominal' => '-123.45', 'tanggal' => '2026-03-01', 'referensi' => 'REVERSAL', 'sumber' => 'Uji', 'koreksi_atas_id' => (string) $original->id]);
        $pimpinan = User::factory()->create()->assignRole('Pimpinan Dinas');
        $this->actingAs($pimpinan);
        $this->assertSame(1, \App\Filament\Resources\PenerimaanBosResource::getEloquentQuery()->count());
        $this->assertFalse(Gate::allows('view', $reversal));
        $this->actingAs($this->operator); $this->approve($reversal);
        $this->assertSame('0.00', app(MonitoringService::class)->summary(new MonitoringFilter())['penerimaan']);
        $this->assertSame('123.45', app(MonitoringService::class)->summary(new MonitoringFilter(['sampai' => '2026-02-28']))['penerimaan']);
        $this->assertSame('123.45', $original->fresh()->nominal);
    }
}
