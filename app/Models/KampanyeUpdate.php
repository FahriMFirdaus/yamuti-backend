<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KampanyeUpdate extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kampanye_updates';

    protected $fillable = [
        'kampanye_id',
        'judul',
        'konten',
        'dana_disalurkan',
        'tanggal_penyaluran',
        'foto_urls',
    ];

    protected $casts = [
        'dana_disalurkan' => 'decimal:2',
        'tanggal_penyaluran' => 'date',
        'foto_urls' => 'array',
    ];

    public function kampanye()
    {
        return $this->belongsTo(Kampanye::class, 'kampanye_id');
    }
}
