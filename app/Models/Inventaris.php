<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventaris extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama_barang',
        'kategori',
        'deskripsi',
        'stok_sekarang',
        'satuan',
        'tanggal_kadaluarsa',
        'lokasi_penyimpanan',
        'stok_minimum',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_kadaluarsa' => 'date',
        'stok_sekarang' => 'integer',
        'stok_minimum' => 'integer',
    ];

    public function mutasi()
    {
        return $this->hasMany(MutasiBarang::class);
    }
}
