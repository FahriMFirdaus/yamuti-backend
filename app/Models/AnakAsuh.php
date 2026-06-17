<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnakAsuh extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'nama',
        'no_kk',
        'no_akte',
        'tempat_lahir',
        'jenis_kelamin',
        'tanggal_lahir',
        'status', 
        'kategori_bayi',
        'tanggal_masuk',
        'keterangan',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_masuk' => 'date',
        'kategori_bayi' => 'boolean',
    ];
}
