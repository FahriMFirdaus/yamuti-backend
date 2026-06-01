<?php

namespace App\Services;

use App\Models\Kunjungan;
use Illuminate\Pagination\LengthAwarePaginator;

class KunjunganService extends BaseService
{
    public function getAllKunjungan(int $perPage = 15): LengthAwarePaginator
    {
        return Kunjungan::latest()->paginate($perPage);
    }

    public function getKunjunganById(string $id): Kunjungan
    {
        return Kunjungan::findOrFail($id);
    }

    public function requestKunjungan(array $data): Kunjungan
    {
        $data['status'] = 'PENDING';
        return Kunjungan::create($data);
    }

    public function approveKunjungan(string $id, string $adminId): Kunjungan
    {
        $kunjungan = Kunjungan::findOrFail($id);
        $kunjungan->update([
            'status' => 'APPROVED',
            'approved_by' => $adminId,
        ]);
        return $kunjungan;
    }
    
    public function rejectKunjungan(string $id, string $adminId): Kunjungan
    {
        $kunjungan = Kunjungan::findOrFail($id);
        $kunjungan->update([
            'status' => 'REJECTED',
            'approved_by' => $adminId,
        ]);
        return $kunjungan;
    }
}
