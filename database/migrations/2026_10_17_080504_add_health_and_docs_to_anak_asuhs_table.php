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
            $table->string('no_bpjs', 50)->nullable()->after('no_akte');
            $table->boolean('has_akta_fisik')->default(false)->after('no_bpjs');
            $table->boolean('has_kk_fisik')->default(false)->after('has_akta_fisik');
            $table->boolean('has_ijazah_fisik')->default(false)->after('has_kk_fisik');
            
            // Pengukuran Fisik & Kesehatan Dasar
            $table->integer('berat_badan')->nullable()->comment('dalam kg')->after('golongan_darah');
            $table->integer('tinggi_badan')->nullable()->comment('dalam cm')->after('berat_badan');
            $table->text('riwayat_alergi')->nullable()->after('riwayat_penyakit');
            $table->text('status_imunisasi')->nullable()->after('riwayat_alergi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anak_asuhs', function (Blueprint $table) {
            $table->dropColumn([
                'no_bpjs',
                'has_akta_fisik',
                'has_kk_fisik',
                'has_ijazah_fisik',
                'berat_badan',
                'tinggi_badan',
                'riwayat_alergi',
                'status_imunisasi',
            ]);
        });
    }
};
