<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kampanye_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kampanye_id')->constrained('kampanyes')->cascadeOnDelete();
            $table->string('judul');
            $table->text('konten');
            $table->decimal('dana_disalurkan', 15, 2)->default(0);
            $table->date('tanggal_penyaluran')->nullable();
            $table->json('foto_urls')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kampanye_updates');
    }
};
