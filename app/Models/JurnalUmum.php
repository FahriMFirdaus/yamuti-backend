<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JurnalUmum extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'jurnal_umums';

    protected $fillable = [
        'nomor_referensi',
        'tanggal_transaksi',
        'keterangan',
        'sumber_transaksi',
        'referensi_id',
        'created_by',
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(JurnalItem::class, 'jurnal_umum_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
