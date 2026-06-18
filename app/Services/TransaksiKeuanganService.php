<?php

namespace App\Services;

use App\Models\TransaksiKeuangan;
use Illuminate\Pagination\LengthAwarePaginator;

class TransaksiKeuanganService extends BaseService
{
    public function getAllTransaksi(int $perPage = 15): LengthAwarePaginator
    {
        return TransaksiKeuangan::latest()->paginate($perPage);
    }

    public function getTransaksiById(string $id): TransaksiKeuangan
    {
        return TransaksiKeuangan::findOrFail($id);
    }

    public function createTransaksi(array $data, string $userId): TransaksiKeuangan
    {
        $data['created_by'] = $userId;
        return TransaksiKeuangan::create($data);
    }
    
    public function getSaldo(?string $jenisKas = null): float
    {
        $query = TransaksiKeuangan::query();
        if ($jenisKas) {
            $query->where('jenis_kas', $jenisKas);
        }
        
        $debit = (clone $query)->where('tipe_transaksi', 'Debit')->sum('nominal');
        $kredit = (clone $query)->where('tipe_transaksi', 'Kredit')->sum('nominal');
        return $debit - $kredit;
    }
}
