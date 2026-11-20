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
            $table->uuid('orang_tua_asuh_id')->nullable()->after('kampanye_id');
            $table->foreign('orang_tua_asuh_id')->references('id')->on('orang_tua_asuhs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donasis', function (Blueprint $table) {
            $table->dropForeign(['orang_tua_asuh_id']);
            $table->dropColumn('orang_tua_asuh_id');
        });
    }
};
