<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Galeri extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'judul',
        'deskripsi',
        'file_url',
        'diunggah_oleh'
    ];

    public function pengunggah()
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }
}
