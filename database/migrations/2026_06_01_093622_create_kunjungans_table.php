<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kunjungans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_tamu');
            $table->string('no_whatsapp');
            $table->integer('jumlah_pengunjung');
            $table->text('maksud');
            $table->dateTime('slot_waktu')->index();
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            
            $table->uuid('branch_id')->nullable();
            $table->uuid('approved_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungans');
    }
};
