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
        // 1. Tabel transaksi_keuangans
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            $table->index('donasi_id');
            $table->index('jenis_kas');
            $table->index('tipe_transaksi');
            
            $table->foreign('donasi_id')
                  ->references('id')
                  ->on('donasis')
                  ->onDelete('set null');
        });

        // 2. Tabel mutasi_barangs
        Schema::table('mutasi_barangs', function (Blueprint $table) {
            $table->index('transaksi_keuangan_id');
            
            $table->foreign('transaksi_keuangan_id')
                  ->references('id')
                  ->on('transaksi_keuangans')
                  ->onDelete('set null');
        });

        // 3. Tabel donasis
        Schema::table('donasis', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
        });

        // 4. Tabel kunjungans
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->index('approved_by');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Tabel kunjungans
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropIndex(['approved_by']);
            $table->dropIndex(['status']);
        });

        // 2. Tabel donasis
        Schema::table('donasis', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
        });

        // 3. Tabel mutasi_barangs
        Schema::table('mutasi_barangs', function (Blueprint $table) {
            $table->dropForeign(['transaksi_keuangan_id']);
            $table->dropIndex(['transaksi_keuangan_id']);
        });

        // 4. Tabel transaksi_keuangans
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            $table->dropForeign(['donasi_id']);
            $table->dropIndex(['donasi_id']);
            $table->dropIndex(['jenis_kas']);
            $table->dropIndex(['tipe_transaksi']);
        });
    }
};
