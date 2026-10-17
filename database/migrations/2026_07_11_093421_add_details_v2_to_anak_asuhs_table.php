<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('anak_asuhs', function (Blueprint $table) {
            $table->string('status_keluarga')->nullable()->after('keterangan'); // Nullable for existing rows, handled in request
            $table->string('tingkat_pendidikan')->nullable()->after('status_keluarga');
            $table->string('nama_sekolah')->nullable()->after('tingkat_pendidikan');
            $table->string('golongan_darah')->nullable()->after('nama_sekolah');
            $table->text('riwayat_penyakit')->nullable()->after('golongan_darah');
            $table->text('catatan_khusus')->nullable()->after('riwayat_penyakit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anak_asuhs', function (Blueprint $table) {
            $table->dropColumn([
                'status_keluarga',
                'tingkat_pendidikan',
                'nama_sekolah',
                'golongan_darah',
                'riwayat_penyakit',
                'catatan_khusus'
            ]);
        });
    }
};
