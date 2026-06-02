<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donasi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'nama_donatur',
        'no_whatsapp',
        'gross_amount',
        'status',
        'payment_type',
        'transaction_id',
        'snap_token',
        'payment_url'
    ];

    public function transaksiKeuangan()
    {
        return $this->hasMany(TransaksiKeuangan::class);
    }
}
