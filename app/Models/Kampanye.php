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

    public function toArray()
    {
        $array = parent::toArray();
        if (!empty($array['thumbnail']) && !str_starts_with($array['thumbnail'], 'http')) {
            $array['thumbnail'] = \Illuminate\Support\Facades\Storage::disk('s3')->url($array['thumbnail']);
        }
        return $array;
    }
}
