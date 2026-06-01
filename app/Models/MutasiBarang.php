<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MutasiBarang extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'inventaris_id',
        'tipe',
        'jumlah',
        'keterangan',
        'tanggal_mutasi',
        'transaksi_keuangan_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'tanggal_mutasi' => 'date',
    ];

    public function inventaris()
    {
        return $this->belongsTo(Inventaris::class);
    }
}
