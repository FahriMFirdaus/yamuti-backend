<?php

namespace App\Models;

use App\Helpers\StorageHelper;
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
        'thumbnail',
    ];

    public function donasi()
    {
        return $this->hasMany(Donasi::class);
    }

    public function updates()
    {
        return $this->hasMany(KampanyeUpdate::class)->latest('tanggal_penyaluran')->latest('created_at');
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (! empty($array['thumbnail'])) {
            $array['thumbnail'] = StorageHelper::getUrl($array['thumbnail']);
        }

        return $array;
    }
}
