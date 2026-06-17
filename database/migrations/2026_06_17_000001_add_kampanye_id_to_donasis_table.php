<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donasis', function (Blueprint $table) {
            $table->uuid('kampanye_id')->nullable()->after('user_id');
            // Menambahkan index untuk mempercepat query pencarian donasi berdasarkan kampanye
            $table->index('kampanye_id');
        });
    }

    public function down(): void
    {
        Schema::table('donasis', function (Blueprint $table) {
            $table->dropIndex(['kampanye_id']);
            $table->dropColumn('kampanye_id');
        });
    }
};
