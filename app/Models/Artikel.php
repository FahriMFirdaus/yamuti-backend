<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Artikel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'judul',
        'slug',
        'konten',
        'kategori_id',
        'file_url',
        'thumbnail_url',
        'penulis_id'
    ];

    public function penulis()
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }

    public function kategoriArtikel()
    {
        return $this->belongsTo(KategoriArtikel::class, 'kategori_id');
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (!empty($array['thumbnail_url']) && !str_starts_with($array['thumbnail_url'], 'http')) {
            $array['thumbnail_url'] = \Illuminate\Support\Facades\Storage::disk('s3')->url($array['thumbnail_url']);
        }
        if (!empty($array['file_url']) && !str_starts_with($array['file_url'], 'http')) {
            $array['file_url'] = \Illuminate\Support\Facades\Storage::disk('s3')->url($array['file_url']);
        }
        return $array;
    }
}
