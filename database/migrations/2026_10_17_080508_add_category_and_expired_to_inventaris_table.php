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
        Schema::table('inventaris', function (Blueprint $table) {
            $table->string('kategori', 50)->default('Lainnya')->after('nama_barang'); // Sembako, Obat-obatan, Pakaian, Perlengkapan Sekolah, Elektronik, Lainnya
            $table->date('tanggal_kadaluarsa')->nullable()->after('satuan');
            $table->string('lokasi_penyimpanan', 100)->nullable()->after('tanggal_kadaluarsa'); // Gudang A, Rak Dapur, Lemari Medis, dll
            $table->integer('stok_minimum')->default(5)->after('lokasi_penyimpanan'); // Ambang batas peringatan stok menipis
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventaris', function (Blueprint $table) {
            $table->dropColumn([
                'kategori',
                'tanggal_kadaluarsa',
                'lokasi_penyimpanan',
                'stok_minimum',
            ]);
        });
    }
};
