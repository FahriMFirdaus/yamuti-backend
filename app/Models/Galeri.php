<?php

namespace App\Models;

use App\Helpers\StorageHelper;
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
        'diunggah_oleh',
    ];

    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute(): ?string
    {
        return $this->file_url;
    }

    public function pengunggah()
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (! empty($array['file_url'])) {
            $array['file_url'] = StorageHelper::getUrl($array['file_url']);
        }

        return $array;
    }
}
