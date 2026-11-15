<?php

namespace App\Models;

use App\Helpers\StorageHelper;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnakAsuh extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'nik',
        'no_kk',
        'no_akte',
        'foto_identitas',
        'tempat_lahir',
        'jenis_kelamin',
        'tanggal_lahir',
        'status',
        'kategori_bayi',
        'tanggal_masuk',
        'keterangan',
        'status_keluarga',
        'tingkat_pendidikan',
        'nama_sekolah',
        'golongan_darah',
        'riwayat_penyakit',
        'catatan_khusus',
        'no_bpjs',
        'has_akta_fisik',
        'has_kk_fisik',
        'has_ijazah_fisik',
        'berat_badan',
        'tinggi_badan',
        'riwayat_alergi',
        'status_imunisasi',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_masuk' => 'date',
        'kategori_bayi' => 'boolean',
        'has_akta_fisik' => 'boolean',
        'has_kk_fisik' => 'boolean',
        'has_ijazah_fisik' => 'boolean',
        'berat_badan' => 'integer',
        'tinggi_badan' => 'integer',
    ];

    public function toArray()
    {
        $array = parent::toArray();
        if (! empty($array['foto_identitas'])) {
            $array['foto_identitas'] = StorageHelper::getUrl($array['foto_identitas']);
        }

        return $array;
    }

    public function orangTuaAsuh()
    {
        return $this->hasMany(OrangTuaAsuh::class, 'anak_asuh_id');
    }

    public function records()
    {
        return $this->hasMany(AnakAsuhRecord::class, 'anak_asuh_id')->latest('tanggal_kejadian')->latest('created_at');
    }
}
