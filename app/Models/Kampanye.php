<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kampanye extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'judul',
        'slug',
        'deskripsi',
        'target_donasi',
        'tanggal_mulai',
        'tanggal_berakhir',
        'status', // Aktif, Selesai, Dibatalkan
        'thumbnail'
    ];

    public function donasi()
    {
        return $this->hasMany(Donasi::class);
    }
}
