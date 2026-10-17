<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrangTuaAsuh extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'orang_tua_asuhs';

    protected $fillable = [
        'user_id',
        'nama_donatur',
        'no_hp',
        'email',
        'anak_asuh_id',
        'nominal_bulanan',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
        'hari_tagihan_per_bulan',
        'catatan_doa',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'nominal_bulanan' => 'float',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'hari_tagihan_per_bulan' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function anakAsuh()
    {
        return $this->belongsTo(AnakAsuh::class, 'anak_asuh_id');
    }
}
