<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'payrolls';

    protected $fillable = [
        'nomor_slip',
        'karyawan_id',
        'periode_bulan',
        'periode_tahun',
        'tanggal_pembayaran',
        'gaji_pokok',
        'tunjangan_jabatan',
        'tunjangan_kehadiran',
        'tunjangan_keluarga',
        'bonus_tambahan',
        'total_pendapatan',
        'potongan_bpjs',
        'potongan_kasbon',
        'potongan_terlambat',
        'potongan_lain',
        'total_potongan',
        'gaji_bersih',
        'status',
        'metode_pembayaran',
        'kas_bank_akun_id',
        'jurnal_umum_id',
        'catatan',
        'disetujui_oleh',
        'disetujui_pada',
        'dibayar_oleh',
        'dibayar_pada',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'periode_bulan' => 'integer',
        'periode_tahun' => 'integer',
        'tanggal_pembayaran' => 'date',
        'gaji_pokok' => 'float',
        'tunjangan_jabatan' => 'float',
        'tunjangan_kehadiran' => 'float',
        'tunjangan_keluarga' => 'float',
        'bonus_tambahan' => 'float',
        'total_pendapatan' => 'float',
        'potongan_bpjs' => 'float',
        'potongan_kasbon' => 'float',
        'potongan_terlambat' => 'float',
        'potongan_lain' => 'float',
        'total_potongan' => 'float',
        'gaji_bersih' => 'float',
        'disetujui_pada' => 'datetime',
        'dibayar_pada' => 'datetime',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function jurnalUmum()
    {
        return $this->belongsTo(JurnalUmum::class, 'jurnal_umum_id');
    }

    public function kasBankAkun()
    {
        return $this->belongsTo(ChartOfAccount::class, 'kas_bank_akun_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'dibayar_oleh');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
