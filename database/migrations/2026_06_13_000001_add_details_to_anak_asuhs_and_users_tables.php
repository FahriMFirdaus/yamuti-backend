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
        // 1. Modifikasi Tabel anak_asuhs
        Schema::table('anak_asuhs', function (Blueprint $table) {
            $table->string('no_kk', 16)->nullable()->after('nama');
            $table->string('no_akte')->nullable()->after('no_kk');
            $table->string('tempat_lahir')->nullable()->after('no_akte');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable()->after('tempat_lahir');
            $table->date('tanggal_masuk')->nullable()->after('kategori_bayi');
            $table->text('keterangan')->nullable()->after('tanggal_masuk');
        });

        // 2. Modifikasi Tabel users (mewakili pegawai/karyawan)
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 16)->unique()->nullable()->after('name');
            $table->string('no_hp')->nullable()->after('email');
            $table->string('skck')->nullable()->after('no_hp'); // Nomor/status SKCK
            $table->text('alamat')->nullable()->after('skck');
            $table->enum('status_pegawai', ['Aktif', 'Nonaktif'])->default('Aktif')->after('alamat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Rollback tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'no_hp', 'skck', 'alamat', 'status_pegawai']);
        });

        // 2. Rollback tabel anak_asuhs
        Schema::table('anak_asuhs', function (Blueprint $table) {
            $table->dropColumn(['no_kk', 'no_akte', 'tempat_lahir', 'jenis_kelamin', 'tanggal_masuk', 'keterangan']);
        });
    }
};
