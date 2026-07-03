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

    public function toArray()
    {
        $array = parent::toArray();
        if (!empty($array['file_url']) && !str_starts_with($array['file_url'], 'http')) {
            $array['file_url'] = \Illuminate\Support\Facades\Storage::disk('s3')->url($array['file_url']);
        }
        return $array;
    }
}
