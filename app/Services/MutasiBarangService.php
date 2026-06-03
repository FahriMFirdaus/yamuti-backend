<?php

namespace App\Services;

use App\Models\Inventaris;
use App\Models\MutasiBarang;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Events\PengadaanBarangDibuat;
use Exception;

class MutasiBarangService extends BaseService
{
    public function getRiwayatMutasi(string $inventarisId, int $perPage = 15): LengthAwarePaginator
    {
        return MutasiBarang::where('inventaris_id', $inventarisId)
            ->orderBy('tanggal_mutasi', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function createMutasi(string $inventarisId, array $data, string $userId): MutasiBarang
    {
        return DB::transaction(function () use ($inventarisId, $data, $userId) {
            $inventaris = Inventaris::lockForUpdate()->findOrFail($inventarisId);

            if ($data['tipe'] === 'keluar' && $inventaris->stok_sekarang < $data['jumlah']) {
                throw new Exception("Stok tidak mencukupi untuk mutasi keluar. Sisa stok: " . $inventaris->stok_sekarang);
            }

            // Update stok inventaris
            if ($data['tipe'] === 'masuk') {
                $inventaris->increment('stok_sekarang', $data['jumlah']);
            } else {
                $inventaris->decrement('stok_sekarang', $data['jumlah']);
            }

            // Record mutasi
            $data['inventaris_id'] = $inventaris->id;
            $data['created_by'] = $userId;
            
            $mutasi = MutasiBarang::create($data);
            
            // Trigger Event Task 3.2.2 jika pengadaan barang baru (tipe: masuk)
            if ($data['tipe'] === 'masuk') {
                event(new PengadaanBarangDibuat($mutasi));
            }
            
            return $mutasi;
        });
    }
}
