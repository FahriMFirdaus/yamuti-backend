<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Keranjang extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'kampanye_id',
        'nominal',
        'pesan',
        'is_anonymous',
        'nama_donatur',
        'no_whatsapp'
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function kampanye()
    {
        return $this->belongsTo(Kampanye::class);
    }
}
