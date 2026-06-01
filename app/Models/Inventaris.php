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
        'deskripsi',
        'stok_sekarang',
        'satuan',
        'created_by',
        'updated_by'
    ];

    public function mutasi()
    {
        return $this->hasMany(MutasiBarang::class);
    }
}
