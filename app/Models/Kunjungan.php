<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kunjungan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama_tamu',
        'no_whatsapp',
        'jumlah_pengunjung',
        'maksud',
        'slot_waktu',
        'status',
        'branch_id',
        'approved_by'
    ];

    protected $casts = [
        'slot_waktu' => 'datetime',
    ];
}
