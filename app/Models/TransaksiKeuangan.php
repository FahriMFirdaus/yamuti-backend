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
        'nominal',
        'deskripsi',
        'donasi_id',
        'created_by'
    ];

    public function donasi()
    {
        return $this->belongsTo(Donasi::class);
    }
}
