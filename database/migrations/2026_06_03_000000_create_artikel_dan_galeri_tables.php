<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artikels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('konten');
            $table->string('kategori')->default('Berita');
            $table->string('thumbnail_url')->nullable();
            $table->uuid('penulis_id');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('penulis_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('galeris', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('file_url');
            $table->uuid('diunggah_oleh');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('diunggah_oleh')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galeris');
        Schema::dropIfExists('artikels');
    }
};
