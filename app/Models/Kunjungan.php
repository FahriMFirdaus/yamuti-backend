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
        'user_id',
        'nama_tamu',
        'no_whatsapp',
        'jumlah_pengunjung',
        'maksud',
        'slot_waktu',
        'is_full_day',
        'status',
        'catatan_admin',
        'branch_id',
        'approved_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'slot_waktu' => 'datetime',
        'is_full_day' => 'boolean',
    ];
}
