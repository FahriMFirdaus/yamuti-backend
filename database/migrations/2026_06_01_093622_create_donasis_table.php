<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donasis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable(); // Bisa null jika donatur adalah Guest
            $table->string('nama_donatur');
            $table->string('no_whatsapp')->nullable();
            $table->decimal('gross_amount', 15, 2);
            $table->enum('status', ['PENDING', 'PAID', 'FAILED'])->default('PENDING');
            $table->string('payment_type')->nullable(); // qris, bank_transfer, ewallet
            $table->string('transaction_id')->nullable()->unique(); // ID dari Payment Gateway
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donasis');
    }
};
