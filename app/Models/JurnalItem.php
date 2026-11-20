<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'jurnal_items';

    protected $fillable = [
        'jurnal_umum_id',
        'chart_of_account_id',
        'debit',
        'kredit',
        'memo',
    ];

    protected $casts = [
        'debit' => 'float',
        'kredit' => 'float',
    ];

    public function jurnal()
    {
        return $this->belongsTo(JurnalUmum::class, 'jurnal_umum_id');
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
