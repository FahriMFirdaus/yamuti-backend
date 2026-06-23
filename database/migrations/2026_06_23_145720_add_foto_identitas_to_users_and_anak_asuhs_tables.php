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
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto_identitas')->nullable()->after('status_pegawai');
        });

        Schema::table('anak_asuhs', function (Blueprint $table) {
            $table->string('foto_identitas')->nullable()->after('no_akte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('foto_identitas');
        });

        Schema::table('anak_asuhs', function (Blueprint $table) {
            $table->dropColumn('foto_identitas');
        });
    }
};
