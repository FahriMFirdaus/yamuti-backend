<?php

namespace App\Services;

use App\Models\AnakAsuh;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class AnakAsuhService extends BaseService
{
    public function getAllAnakAsuh(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = AnakAsuh::with(['orangTuaAsuh', 'records']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('asal_kota', 'like', "%{$search}%");
            });
        }

        if (! empty($status) && $status !== 'ALL') {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getAnakAsuhById(string $id): AnakAsuh
    {
        return AnakAsuh::with('records')->findOrFail($id);
    }

    public function createAnakAsuh(array $data, string $userId): AnakAsuh
    {
        $data['created_by'] = $userId;

        if (isset($data['foto_identitas'])) {
            $data['foto_identitas'] = \App\Helpers\StorageHelper::storeFile($data['foto_identitas'], 'identitas/anak_asuh');
        }

        return AnakAsuh::create($data);
    }

    public function updateAnakAsuh(string $id, array $data, string $userId): AnakAsuh
    {
        $anak = AnakAsuh::findOrFail($id);
        $data['updated_by'] = $userId;

        if (isset($data['foto_identitas'])) {
            $data['foto_identitas'] = \App\Helpers\StorageHelper::storeFile($data['foto_identitas'], 'identitas/anak_asuh');
        }

        $anak->update($data);

        return $anak;
    }

    public function deleteAnakAsuh(string $id): bool
    {
        $anak = AnakAsuh::findOrFail($id);

        return $anak->delete();
    }
}
