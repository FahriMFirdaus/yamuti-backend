<?php

namespace App\Events;

use App\Models\MutasiBarang;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PengadaanBarangDibuat
{
    use Dispatchable, SerializesModels;

    public $mutasi;

    /**
     * Create a new event instance.
     *
     * @param MutasiBarang $mutasi
     */
    public function __construct(MutasiBarang $mutasi)
    {
        $this->mutasi = $mutasi;
    }
}
