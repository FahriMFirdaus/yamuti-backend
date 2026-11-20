<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'kode_akun',
        'nama_akun',
        'tipe_akun',
        'saldo_normal',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function jurnalItems()
    {
        return $this->hasMany(JurnalItem::class, 'chart_of_account_id');
    }
}
