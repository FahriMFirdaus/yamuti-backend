<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kampanye;
use App\Models\KampanyeUpdate;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KampanyeUpdateController extends Controller
{
    use ApiResponse;

    // GET /kampanye/{kampanyeId}/updates (Publik)
    public function index($kampanyeId): JsonResponse
    {
        $kampanye = Kampanye::where('id', $kampanyeId)->orWhere('slug', $kampanyeId)->firstOrFail();
        $updates = $kampanye->updates()->get();

        return $this->successResponse($updates, 'Kabar penyaluran berhasil diambil');
    }

    // POST /kampanye/{kampanyeId}/updates (Admin)
    public function store(Request $request, $kampanyeId): JsonResponse
    {
        $kampanye = Kampanye::where('id', $kampanyeId)->orWhere('slug', $kampanyeId)->firstOrFail();

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
            'dana_disalurkan' => 'nullable|numeric|min:0',
            'tanggal_penyaluran' => 'nullable|date',
            'foto_urls' => 'nullable|array',
            'foto_urls.*' => 'nullable|string',
            'fotos' => 'nullable|array',
            'fotos.*' => 'file|image|max:5120',
        ]);

        $fotoUrls = $validated['foto_urls'] ?? [];

        if ($request->hasFile('fotos')) {
            foreach ($request->file('fotos') as $foto) {
                $path = $foto->store('kampanye-updates', 's3');
                $fotoUrls[] = Storage::disk('s3')->url($path);
            }
        }

        $update = KampanyeUpdate::create([
            'kampanye_id' => $kampanye->id,
            'judul' => $validated['judul'],
            'konten' => $validated['konten'],
            'dana_disalurkan' => $validated['dana_disalurkan'] ?? 0,
            'tanggal_penyaluran' => $validated['tanggal_penyaluran'] ?? now(),
            'foto_urls' => $fotoUrls,
        ]);

        return $this->successResponse($update, 'Kabar penyaluran berhasil ditambahkan', 201);
    }

    // DELETE /kampanye/updates/{id} (Admin)
    public function destroy($id): JsonResponse
    {
        $update = KampanyeUpdate::findOrFail($id);
        $update->delete();

        return $this->successResponse(null, 'Kabar penyaluran berhasil dihapus');
    }
}
