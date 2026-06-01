<?php

namespace App\Services;

use App\Models\Inventaris;
use App\Models\MutasiBarang;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventarisService extends BaseService
{
    public function getAllInventaris(int $perPage = 15): LengthAwarePaginator
    {
        return Inventaris::latest()->paginate($perPage);
    }

    public function getInventarisById(string $id): Inventaris
    {
        return Inventaris::findOrFail($id);
    }

    public function createInventaris(array $data, string $userId): Inventaris
    {
        $data['created_by'] = $userId;
        return DB::transaction(function () use ($data, $userId) {
            $inventaris = Inventaris::create($data);
            
            if ($data['stok_sekarang'] > 0) {
                MutasiBarang::create([
                    'inventaris_id' => $inventaris->id,
                    'tipe' => 'masuk',
                    'jumlah' => $data['stok_sekarang'],
                    'keterangan' => 'Stok awal sistem',
                    'tanggal_mutasi' => now(),
                    'created_by' => $userId,
                ]);
            }
            
            return $inventaris;
        });
    }

    public function updateInventaris(string $id, array $data, string $userId): Inventaris
    {
        $inventaris = Inventaris::findOrFail($id);
        $data['updated_by'] = $userId;
        $inventaris->update($data);
        return $inventaris;
    }

    public function deleteInventaris(string $id): bool
    {
        $inventaris = Inventaris::findOrFail($id);
        return $inventaris->delete();
    }
}
