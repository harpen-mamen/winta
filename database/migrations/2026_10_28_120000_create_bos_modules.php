<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function workflow(Blueprint $table): void
    {
        $table->string('status')->default('draft')->index();
        $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
        $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
        $table->timestamp('verified_at')->nullable();
        $table->text('catatan')->nullable();
        $table->timestamps();
    }

    public function up(): void
    {
        Schema::create('kecamatans', function (Blueprint $t) {
            $t->id(); $t->string('kode', 30)->unique(); $t->string('nama'); $t->boolean('aktif')->default(true); $t->timestamps();
        });
        Schema::create('sekolahs', function (Blueprint $t) {
            $t->id(); $t->char('npsn', 8)->unique(); $t->string('nama'); $t->string('status_sekolah');
            $t->foreignId('kecamatan_id')->constrained('kecamatans')->restrictOnDelete();
            $t->text('alamat')->nullable(); $t->boolean('aktif')->default(true); $t->boolean('is_demo')->default(false); $t->timestamps();
        });
        Schema::create('tahun_anggarans', function (Blueprint $t) {
            $t->id(); $t->unsignedSmallInteger('tahun')->unique(); $t->boolean('aktif')->default(true); $t->boolean('dikunci')->default(false); $t->timestamps();
        });
        Schema::create('jenis_bos', function (Blueprint $t) {
            $t->id(); $t->string('kode', 30)->unique(); $t->string('nama'); $t->text('sumber')->nullable(); $t->boolean('aktif')->default(true); $t->timestamps();
        });
        Schema::create('data_murids', function (Blueprint $t) {
            $t->id(); $t->foreignId('sekolah_id')->constrained('sekolahs')->restrictOnDelete(); $t->unsignedSmallInteger('tahun_pendataan');
            $t->unsignedInteger('jumlah'); $t->string('sumber'); $t->date('tanggal'); $t->timestamps(); $t->unique(['sekolah_id', 'tahun_pendataan']);
        });
        Schema::create('komponen_belanja', function (Blueprint $t) {
            $t->id(); $t->foreignId('tahun_anggaran_id')->constrained('tahun_anggarans')->restrictOnDelete();
            $t->string('kode', 30); $t->string('nama'); $t->string('sumber'); $t->boolean('aktif')->default(true); $t->timestamps(); $t->unique(['tahun_anggaran_id', 'kode']);
        });
        Schema::create('pagu_bos', function (Blueprint $t) {
            $t->id(); $t->foreignId('sekolah_id')->constrained('sekolahs')->restrictOnDelete();
            $t->foreignId('tahun_anggaran_id')->constrained('tahun_anggarans')->restrictOnDelete();
            $t->foreignId('jenis_bos_id')->constrained('jenis_bos')->restrictOnDelete();
            $t->decimal('pagu_awal', 18, 2); $t->decimal('pagu_berlaku', 18, 2); $t->date('tanggal');
            $t->string('nomor_dokumen'); $t->string('sumber'); $this->workflow($t);
            $t->unique(['sekolah_id', 'tahun_anggaran_id', 'jenis_bos_id']);
        });
        Schema::create('perubahan_pagu', function (Blueprint $t) {
            $t->id(); $t->foreignId('pagu_bos_id')->constrained('pagu_bos')->restrictOnDelete();
            $t->decimal('nilai_lama', 18, 2); $t->decimal('nilai_baru', 18, 2); $t->date('tanggal'); $t->string('sumber'); $t->text('alasan'); $this->workflow($t);
        });
        Schema::create('tahap_penyalurans', function (Blueprint $t) {
            $t->id(); $t->foreignId('tahun_anggaran_id')->constrained('tahun_anggarans')->restrictOnDelete();
            $t->foreignId('jenis_bos_id')->constrained('jenis_bos')->restrictOnDelete(); $t->string('kode', 30); $t->string('nama');
            $t->unsignedSmallInteger('urutan'); $t->date('jadwal')->nullable(); $t->timestamps(); $t->unique(['tahun_anggaran_id', 'jenis_bos_id', 'kode']);
        });
        Schema::create('penyaluran_bos', function (Blueprint $t) {
            $t->id(); $t->foreignId('pagu_bos_id')->constrained('pagu_bos')->restrictOnDelete();
            $t->foreignId('tahap_penyaluran_id')->constrained('tahap_penyalurans')->restrictOnDelete();
            $t->decimal('nominal', 18, 2); $t->date('tanggal')->index(); $t->string('referensi')->unique(); $t->string('sumber'); $this->workflow($t);
        });
        Schema::create('penerimaan_bos', function (Blueprint $t) {
            $t->id(); $t->foreignId('pagu_bos_id')->constrained('pagu_bos')->restrictOnDelete();
            $t->foreignId('penyaluran_bos_id')->nullable()->constrained('penyaluran_bos')->restrictOnDelete();
            $t->decimal('nominal', 18, 2); $t->date('tanggal')->index(); $t->string('referensi')->unique(); $t->string('sumber'); $this->workflow($t);
        });
        Schema::create('rkas', function (Blueprint $t) {
            $t->id(); $t->foreignId('pagu_bos_id')->constrained('pagu_bos')->restrictOnDelete(); $t->unsignedSmallInteger('versi');
            $t->date('tanggal'); $t->string('sumber'); $this->workflow($t); $t->unique(['pagu_bos_id', 'versi']);
        });
        Schema::create('rkas_details', function (Blueprint $t) {
            $t->id(); $t->foreignId('rkas_id')->constrained('rkas')->restrictOnDelete();
            $t->foreignId('komponen_belanja_id')->constrained('komponen_belanja')->restrictOnDelete();
            $t->decimal('nominal', 18, 2); $t->timestamps(); $t->unique(['rkas_id', 'komponen_belanja_id']);
        });
        Schema::create('realisasi_bos', function (Blueprint $t) {
            $t->id(); $t->foreignId('pagu_bos_id')->constrained('pagu_bos')->restrictOnDelete();
            $t->foreignId('rkas_detail_id')->constrained('rkas_details')->restrictOnDelete();
            $t->string('mode'); $t->date('periode_awal'); $t->date('periode_akhir'); $t->date('tanggal')->index();
            $t->decimal('nominal', 18, 2); $t->string('referensi')->unique(); $t->string('sumber'); $this->workflow($t);
            $t->index(['pagu_bos_id', 'periode_awal', 'periode_akhir']);
        });
        foreach (['saldo_awal_bos', 'penyesuaian_saldo', 'saldo_dilaporkans'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name) {
                $t->id(); $t->foreignId('pagu_bos_id')->constrained('pagu_bos')->restrictOnDelete();
                if ($name === 'saldo_awal_bos') $t->unique('pagu_bos_id');
                $t->decimal('nominal', 18, 2); $t->date('tanggal')->index(); $t->string('referensi')->unique(); $t->string('sumber'); $this->workflow($t);
                if ($name === 'saldo_dilaporkans') $t->unique(['pagu_bos_id', 'tanggal']);
            });
        }
        Schema::create('dokumen_pendukung', function (Blueprint $t) {
            $t->id(); $t->morphs('target'); $t->string('nama'); $t->string('path'); $t->string('mime');
            $t->unsignedBigInteger('ukuran'); $t->string('checksum', 64); $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
        });
        Schema::create('verifikasis', function (Blueprint $t) {
            $t->id(); $t->morphs('target'); $t->string('status_sebelum'); $t->string('status_sesudah'); $t->text('catatan')->nullable();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete(); $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->nullableMorphs('target'); $t->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('aksi'); $t->json('sebelum')->nullable(); $t->json('sesudah')->nullable(); $t->timestamps();
        });
        Schema::create('import_batches', function (Blueprint $t) {
            $t->id(); $t->string('tipe'); $t->string('nama_file'); $t->string('checksum', 64)->unique(); $t->string('status');
            $t->unsignedInteger('jumlah')->default(0); $t->json('errors')->nullable(); $t->foreignId('user_id')->constrained('users')->restrictOnDelete(); $t->timestamps();
        });
        Schema::create('evaluasis', function (Blueprint $t) {
            $t->id(); $t->foreignId('pagu_bos_id')->constrained('pagu_bos')->restrictOnDelete(); $t->text('catatan');
            $t->date('tanggal'); $t->foreignId('user_id')->constrained('users')->restrictOnDelete(); $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['evaluasis', 'import_batches', 'audit_logs', 'verifikasis', 'dokumen_pendukung', 'saldo_dilaporkans', 'penyesuaian_saldo', 'saldo_awal_bos', 'realisasi_bos', 'rkas_details', 'rkas', 'penerimaan_bos', 'penyaluran_bos', 'tahap_penyalurans', 'perubahan_pagu', 'pagu_bos', 'komponen_belanja', 'data_murids', 'jenis_bos', 'tahun_anggarans', 'sekolahs', 'kecamatans'] as $table) Schema::dropIfExists($table);
    }
};
