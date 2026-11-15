<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnakAsuhRecord extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'anak_asuh_records';

    protected $fillable = [
        'anak_asuh_id',
        'tipe',
        'judul',
        'deskripsi',
        'tanggal_kejadian',
        'semester',
        'nilai_rata_rata',
        'lampiran_urls',
        'created_by',
    ];

    protected $casts = [
        'tanggal_kejadian' => 'date',
        'lampiran_urls' => 'array',
    ];

    public function anakAsuh()
    {
        return $this->belongsTo(AnakAsuh::class, 'anak_asuh_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
