<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bagan Akun (Chart of Accounts / COA)
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_akun', 30)->unique(); // e.g. 1-1001, 4-1001, 5-1001
            $table->string('nama_akun');
            $table->enum('tipe_akun', ['Aset', 'Kewajiban', 'Aset Neto', 'Penerimaan', 'Beban']);
            $table->enum('saldo_normal', ['Debit', 'Kredit']);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Header Jurnal Umum (General Journal Entry)
        Schema::create('jurnal_umums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nomor_referensi', 50)->unique(); // e.g. JUR-20261120-0001
            $table->date('tanggal_transaksi');
            $table->string('keterangan');
            $table->string('sumber_transaksi')->nullable(); // 'Donasi', 'POTA', 'Kas Manual', 'Inventaris', dll.
            $table->string('referensi_id')->nullable(); // UUID dari donasis atau transaksi_keuangans
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Item Rincian Jurnal (Double-Entry Debit & Kredit)
        Schema::create('jurnal_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('jurnal_umum_id')->constrained('jurnal_umums')->onDelete('cascade');
            $table->foreignUuid('chart_of_account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();
        });

        // 4. Perluas tabel transaksi_keuangans dengan kategori beban & bukti kuitansi
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            $table->string('kategori_pengeluaran')->nullable()->after('tipe_transaksi');
            $table->string('bukti_transaksi')->nullable()->after('deskripsi');
            $table->foreignUuid('kampanye_id')->nullable()->after('donasi_id')->constrained('kampanyes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_keuangans', function (Blueprint $table) {
            $table->dropForeign(['kampanye_id']);
            $table->dropColumn(['kategori_pengeluaran', 'bukti_transaksi', 'kampanye_id']);
        });

        Schema::dropIfExists('jurnal_items');
        Schema::dropIfExists('jurnal_umums');
        Schema::dropIfExists('chart_of_accounts');
    }
};
