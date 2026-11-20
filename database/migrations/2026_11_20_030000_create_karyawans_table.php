<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nip', 30)->unique();
            $table->string('nik', 16)->nullable()->index();
            $table->string('nama_lengkap');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->default('Laki-laki');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('no_hp', 25)->nullable();
            $table->string('email')->nullable();
            $table->text('alamat')->nullable();
            $table->enum('tipe_sdm', ['Pengasuh/Ustadz', 'Staf/Amil'])->default('Pengasuh/Ustadz')->index();
            $table->string('jabatan');
            $table->string('divisi')->nullable();
            $table->enum('status_kepegawaian', ['Tetap', 'Kontrak', 'Honorer', 'Magang'])->default('Tetap');
            $table->enum('status_aktif', ['Aktif', 'Nonaktif', 'Cuti'])->default('Aktif')->index();
            $table->date('tanggal_bergabung')->nullable();
            $table->date('tanggal_berhenti')->nullable();
            $table->string('nama_bank', 50)->nullable();
            $table->string('nomor_rekening', 50)->nullable();
            $table->string('atas_nama_rekening')->nullable();
            $table->decimal('gaji_pokok_standar', 15, 2)->default(0);
            $table->decimal('tunjangan_tetap_standar', 15, 2)->default(0);
            $table->string('foto')->nullable();
            $table->text('catatan')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawans');
    }
};
