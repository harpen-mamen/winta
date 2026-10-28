<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $base = ['monitoring.view', 'reports.export', 'documents.download', 'finance.verify', 'data.write', 'users.manage', 'roles.manage', 'imports.run', 'evaluations.write'];
        foreach (config('bos-modules') as $key => $module) {
            $base[] = $key.'.view';
            if (! $module['readonly']) $base[] = $key.'.write';
        }
        foreach ($base as $permission) \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $common = ['monitoring.view', 'reports.export', 'documents.download'];
        foreach (config('bos-modules') as $key => $module) if (! in_array($key, ['audit_logs', 'import_batches', 'verifikasis', 'dokumen_pendukung'])) $common[] = $key.'.view';
        $input = ['data.write', 'imports.run', 'import_batches.view', 'dokumen_pendukung.view', 'verifikasis.view', 'sekolahs.write', 'data_murids.write', 'rkas_details.write'];
        foreach (config('bos-modules') as $key => $module) if ($module['finance']) $input[] = $key.'.write';
        $sets = [
            'Super Admin' => $base,
            'Operator BOS' => array_merge($common, $input),
            'Verifikator' => array_merge($common, ['finance.verify', 'verifikasis.view', 'dokumen_pendukung.view']),
            'Kepala Bidang SMP' => array_merge($common, ['evaluations.write', 'evaluasis.write', 'data.write', 'verifikasis.view']),
            'Pimpinan Dinas' => $common,
        ];
        foreach ($sets as $name => $permissions) \Spatie\Permission\Models\Role::findOrCreate($name, 'web')->syncPermissions($permissions);
        \App\Models\TahunAnggaran::firstOrCreate(['tahun' => 2026], ['aktif' => true, 'dikunci' => false]);
        \App\Models\JenisBos::firstOrCreate(['kode' => 'REGULER'], ['nama' => 'BOS Reguler', 'sumber' => 'Referensi awal; sesuaikan dengan dokumen penetapan instansi.', 'aktif' => true]);
        if (app()->environment('local')) User::where('email', 'admin@bos-smp.test')->first()?->assignRole('Super Admin');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
