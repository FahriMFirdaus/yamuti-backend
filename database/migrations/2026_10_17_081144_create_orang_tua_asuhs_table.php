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
        Schema::create('orang_tua_asuhs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Akun donatur jika terdaftar
            $table->string('nama_donatur');
            $table->string('no_hp', 30);
            $table->string('email')->nullable();

            // Relasi ke Anak Asuh yang diasuh
            $table->foreignUuid('anak_asuh_id')->constrained('anak_asuhs')->onDelete('cascade');

            // Komitmen Finansial Bulanan
            $table->decimal('nominal_bulanan', 12, 2)->default(100000); // Misal: Rp 100.000, Rp 250.000, Rp 500.000
            $table->enum('status', ['Aktif', 'Jeda', 'Selesai'])->default('Aktif');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->integer('hari_tagihan_per_bulan')->default(1); // Tanggal 1, 5, atau 10 setiap bulan
            $table->text('catatan_doa')->nullable(); // Pesan doa khusus dari donatur untuk anak asuhnya

            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orang_tua_asuhs');
    }
};
