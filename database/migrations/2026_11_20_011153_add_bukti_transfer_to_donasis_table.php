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
        Schema::table('donasis', function (Blueprint $table) {
            $table->string('bukti_transfer')->nullable()->after('payment_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donasis', function (Blueprint $table) {
            $table->dropColumn('bukti_transfer');
        });
    }
};
