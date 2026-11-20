<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiKeuangan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'jenis_kas',
        'tipe_transaksi',
        'kategori_pengeluaran',
        'nominal',
        'deskripsi',
        'bukti_transaksi',
        'kampanye_id',
        'donasi_id',
        'created_by',
    ];

    public function kampanye()
    {
        return $this->belongsTo(Kampanye::class);
    }

    protected $appends = ['status', 'amountRaw'];

    public function donasi()
    {
        return $this->belongsTo(Donasi::class);
    }

    public function mutasiBarang()
    {
        return $this->hasMany(MutasiBarang::class);
    }

    // Accessor untuk kompatibilitas Frontend (Draft/Selesai)
    public function getStatusAttribute()
    {
        return $this->nominal == 0 ? 'Draft' : 'Selesai';
    }

    // Accessor untuk kompatibilitas Frontend (amountRaw)
    public function getAmountRawAttribute()
    {
        return $this->nominal;
    }
}
