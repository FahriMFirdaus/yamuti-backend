<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anak_asuhs', function (Blueprint $table) {
            $table->string('nik', 16)->unique()->nullable()->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('anak_asuhs', function (Blueprint $table) {
            $table->dropColumn('nik');
        });
    }
};
