<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop constraint for ENUM in postgres if driver is pgsql
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE transaksi_keuangans DROP CONSTRAINT IF EXISTS transaksi_keuangans_jenis_kas_check;');
        }
        
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            $table->string('jenis_kas', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            //
        });
    }
};
