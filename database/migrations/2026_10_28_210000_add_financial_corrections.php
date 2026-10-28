<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['penyaluran_bos', 'penerimaan_bos', 'realisasi_bos'] as $name) Schema::table($name, function (Blueprint $t) use ($name) {
            $t->foreignId('koreksi_atas_id')->nullable()->unique()->constrained($name)->restrictOnDelete();
        });
    }
    public function down(): void
    {
        foreach (['penyaluran_bos', 'penerimaan_bos', 'realisasi_bos'] as $name) Schema::table($name, function (Blueprint $t) { $t->dropConstrainedForeignId('koreksi_atas_id'); });
    }
};
