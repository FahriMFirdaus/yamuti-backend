<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KategoriArtikel extends Model
{
    use HasUuids;

    protected $fillable = [
        'nama_kategori',
        'slug'
    ];

    public function artikel()
    {
        return $this->hasMany(Artikel::class, 'kategori_id');
    }
}
