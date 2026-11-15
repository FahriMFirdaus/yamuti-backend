<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anak_asuh_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('anak_asuh_id')->constrained('anak_asuhs')->cascadeOnDelete();
            $table->enum('tipe', ['akademik', 'kesehatan', 'prestasi', 'kegiatan', 'umum'])->default('umum');
            $table->string('judul');
            $table->text('deskripsi');
            $table->date('tanggal_kejadian')->nullable();
            $table->string('semester', 20)->nullable();
            $table->string('nilai_rata_rata', 10)->nullable();
            $table->json('lampiran_urls')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anak_asuh_records');
    }
};
