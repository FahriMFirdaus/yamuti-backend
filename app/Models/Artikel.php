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
        'kategori',
        'thumbnail_url',
        'penulis_id'
    ];

    public function penulis()
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }
}
