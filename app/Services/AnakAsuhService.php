<?php

namespace App\Services;

use App\Models\AnakAsuh;
use Illuminate\Pagination\LengthAwarePaginator;

class AnakAsuhService extends BaseService
{
    public function getAllAnakAsuh(int $perPage = 15): LengthAwarePaginator
    {
        return AnakAsuh::latest()->paginate($perPage);
    }

    public function getAnakAsuhById(string $id): AnakAsuh
    {
        return AnakAsuh::findOrFail($id);
    }

    public function createAnakAsuh(array $data, string $userId): AnakAsuh
    {
        $data['created_by'] = $userId;
        
        if (isset($data['foto_identitas'])) {
            $path = $data['foto_identitas']->store('identitas/anak_asuh', 's3');
            $data['foto_identitas'] = \Illuminate\Support\Facades\Storage::disk('s3')->url($path);
        }

        return AnakAsuh::create($data);
    }

    public function updateAnakAsuh(string $id, array $data, string $userId): AnakAsuh
    {
        $anak = AnakAsuh::findOrFail($id);
        $data['updated_by'] = $userId;

        if (isset($data['foto_identitas'])) {
            $path = $data['foto_identitas']->store('identitas/anak_asuh', 's3');
            $data['foto_identitas'] = \Illuminate\Support\Facades\Storage::disk('s3')->url($path);
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
