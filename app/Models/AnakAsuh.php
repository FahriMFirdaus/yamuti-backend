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
    ];

    /**
     * Memaksa konversi boolean menjadi string 'true' / 'false' untuk kompatibilitas PostgreSQL
     */
    protected function kategoriBayi(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => (bool) $value,
            set: fn ($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
        );
    }
}
