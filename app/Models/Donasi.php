<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donasi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'payment_group_id',
        'user_id',
        'kampanye_id',
        'orang_tua_asuh_id',
        'nama_donatur',
        'no_whatsapp',
        'pesan',
        'gross_amount',
        'status',
        'payment_type',
        'transaction_id',
        'snap_token',
        'payment_url',
        'bukti_transfer',
        'is_anonymous',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function kampanye()
    {
        return $this->belongsTo(Kampanye::class);
    }

    public function orangTuaAsuh()
    {
        return $this->belongsTo(OrangTuaAsuh::class, 'orang_tua_asuh_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaksiKeuangan()
    {
        return $this->hasMany(TransaksiKeuangan::class);
    }

    public function toArray()
    {
        $array = parent::toArray();

        if (isset($array['bukti_transfer'])) {
            $array['bukti_transfer_url'] = \App\Helpers\StorageHelper::getUrl($array['bukti_transfer']);
        } else {
            $array['bukti_transfer_url'] = null;
        }

        // Proteksi Privasi Hamba Allah
        if ($this->is_anonymous) {
            // Hilangkan relasi user agar avatar asli tidak bocor ke publik
            if (isset($array['user'])) {
                $array['user'] = null;
            }

            // Opsional: Pastikan nama_donatur selalu disamarkan jika API diakses tanpa sengaja membocorkan
            if (empty($array['nama_donatur']) || strtolower($array['nama_donatur']) !== 'hamba allah') {
                $array['nama_donatur'] = 'Hamba Allah';
            }
        }

        return $array;
    }
}
