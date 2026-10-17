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
        Schema::create('keranjangs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('kampanye_id')->nullable()->constrained('kampanyes')->nullOnDelete();
            $table->decimal('nominal', 15, 2);
            $table->string('pesan', 500)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('nama_donatur')->nullable();
            $table->string('no_whatsapp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keranjangs');
    }
};
