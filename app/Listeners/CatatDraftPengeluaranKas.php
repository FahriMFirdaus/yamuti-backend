<?php

namespace App\Listeners;

use App\Events\PengadaanBarangDibuat;
use App\Models\TransaksiKeuangan;

class CatatDraftPengeluaranKas
{
    /**
     * Handle the event.
     *
     * @param  \App\Events\PengadaanBarangDibuat  $event
     * @return void
     */
    public function handle(PengadaanBarangDibuat $event)
    {
        $mutasi = $event->mutasi;

        // Buat Draft Transaksi Pengeluaran
        TransaksiKeuangan::create([
            'jenis_kas' => 'Pusat', // Asumsi pengadaan terpusat, bisa diubah
            'tipe_transaksi' => 'Kredit',
            'nominal' => 0, // Dibiarkan 0 dulu agar bendahara bisa mengisi secara manual (Draft)
            'deskripsi' => 'DRAFT: Pembelian ' . $mutasi->inventaris->nama_barang . ' (sebanyak ' . $mutasi->jumlah . ' ' . $mutasi->inventaris->satuan . ')'
        ]);
    }
}
