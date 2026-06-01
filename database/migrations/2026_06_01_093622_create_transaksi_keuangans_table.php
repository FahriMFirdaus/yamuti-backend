<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_keuangans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id')->nullable(); // Untuk memisahkan kas pusat & cabang
            $table->enum('jenis_kas', ['Pusat', 'Cabang']);
            $table->enum('tipe_transaksi', ['Debit', 'Kredit']); // Debit (Masuk), Kredit (Keluar)
            $table->decimal('nominal', 15, 2);
            $table->text('deskripsi');
            $table->uuid('donasi_id')->nullable(); // Jika pemasukan berasal dari pembagian donasi (split rule)
            
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_keuangans');
    }
};
