<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Karyawan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'karyawans';

    protected $fillable = [
        'nip',
        'nik',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'no_hp',
        'email',
        'alamat',
        'tipe_sdm',
        'jabatan',
        'divisi',
        'status_kepegawaian',
        'status_aktif',
        'tanggal_bergabung',
        'tanggal_berhenti',
        'nama_bank',
        'nomor_rekening',
        'atas_nama_rekening',
        'gaji_pokok_standar',
        'tunjangan_tetap_standar',
        'foto',
        'catatan',
        'user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_bergabung' => 'date',
        'tanggal_berhenti' => 'date',
        'gaji_pokok_standar' => 'float',
        'tunjangan_tetap_standar' => 'float',
    ];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'karyawan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
