<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_barangs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('inventaris_id')->constrained('inventaris')->onDelete('cascade');
            $table->enum('tipe', ['masuk', 'keluar', 'rusak']);
            $table->integer('jumlah');
            $table->text('keterangan')->nullable();
            $table->date('tanggal_mutasi');
            
            $table->uuid('transaksi_keuangan_id')->nullable(); // Relasi ke trigger finansial
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_barangs');
    }
};
