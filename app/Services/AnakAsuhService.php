<?php

namespace App\Services;

use App\Models\AnakAsuh;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class AnakAsuhService extends BaseService
{
    public function getAllAnakAsuh(int $perPage = 15): LengthAwarePaginator
    {
        return AnakAsuh::with(['orangTuaAsuh', 'records'])
            ->latest()
            ->paginate($perPage);
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
