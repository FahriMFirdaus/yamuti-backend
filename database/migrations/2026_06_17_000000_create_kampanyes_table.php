<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kampanyes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->decimal('target_donasi', 15, 2)->default(0);
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_berakhir')->nullable();
            $table->string('status')->default('Aktif'); // Aktif, Selesai, Dibatalkan
            $table->string('thumbnail')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kampanyes');
    }
};
