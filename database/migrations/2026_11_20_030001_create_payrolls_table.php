<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nomor_slip', 50)->unique();
            $table->foreignUuid('karyawan_id')->constrained('karyawans')->onDelete('restrict');
            $table->unsignedTinyInteger('periode_bulan');
            $table->unsignedSmallInteger('periode_tahun');
            $table->date('tanggal_pembayaran')->nullable();

            // Allowances / Pendapatan
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('tunjangan_jabatan', 15, 2)->default(0);
            $table->decimal('tunjangan_kehadiran', 15, 2)->default(0);
            $table->decimal('tunjangan_keluarga', 15, 2)->default(0);
            $table->decimal('bonus_tambahan', 15, 2)->default(0);
            $table->decimal('total_pendapatan', 15, 2)->default(0);

            // Deductions / Potongan
            $table->decimal('potongan_bpjs', 15, 2)->default(0);
            $table->decimal('potongan_kasbon', 15, 2)->default(0);
            $table->decimal('potongan_terlambat', 15, 2)->default(0);
            $table->decimal('potongan_lain', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);

            // Gaji Bersih (Take Home Pay)
            $table->decimal('gaji_bersih', 15, 2)->default(0);

            // Status & Accounting
            $table->enum('status', ['Draft', 'Disetujui', 'Dibayar'])->default('Draft')->index();
            $table->enum('metode_pembayaran', ['Transfer Bank', 'Kas Tunai'])->default('Transfer Bank');
            $table->foreignUuid('kas_bank_akun_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->foreignUuid('jurnal_umum_id')->nullable()->constrained('jurnal_umums')->onDelete('set null');
            $table->text('catatan')->nullable();

            // Approvals & Audit Trails
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('disetujui_pada')->nullable();
            $table->foreignId('dibayar_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('dibayar_pada')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['karyawan_id', 'periode_bulan', 'periode_tahun'], 'karyawan_periode_payroll_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
