<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnakAsuh;
use App\Models\AnakAsuhRecord;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnakAsuhRecordController extends Controller
{
    use ApiResponse;

    // GET /anak-asuh/{anakAsuhId}/records
    public function index($anakAsuhId): JsonResponse
    {
        $anak = AnakAsuh::findOrFail($anakAsuhId);
        $records = $anak->records()->with('creator:id,name')->get();

        return $this->successResponse($records, 'Catatan perkembangan anak asuh berhasil diambil');
    }

    // POST /anak-asuh/{anakAsuhId}/records (Admin)
    public function store(Request $request, $anakAsuhId): JsonResponse
    {
        $anak = AnakAsuh::findOrFail($anakAsuhId);

        $validated = $request->validate([
            'tipe' => 'required|in:akademik,kesehatan,prestasi,kegiatan,umum',
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'tanggal_kejadian' => 'nullable|date',
            'semester' => 'nullable|string|max:20',
            'nilai_rata_rata' => 'nullable|string|max:10',
            'lampiran_urls' => 'nullable|array',
            'lampiran_urls.*' => 'nullable|string',
            'lampirans' => 'nullable|array',
            'lampirans.*' => 'file|max:5120',
        ]);

        $urls = $validated['lampiran_urls'] ?? [];

        if ($request->hasFile('lampirans')) {
            foreach ($request->file('lampirans') as $file) {
                $path = $file->store('anak-asuh-records', 's3');
                $urls[] = Storage::disk('s3')->url($path);
            }
        }

        $record = AnakAsuhRecord::create([
            'anak_asuh_id' => $anak->id,
            'tipe' => $validated['tipe'],
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'tanggal_kejadian' => $validated['tanggal_kejadian'] ?? now(),
            'semester' => $validated['semester'] ?? null,
            'nilai_rata_rata' => $validated['nilai_rata_rata'] ?? null,
            'lampiran_urls' => $urls,
            'created_by' => $request->user()?->id,
        ]);

        return $this->successResponse($record, 'Catatan perkembangan berhasil ditambahkan', 201);
    }

    // DELETE /anak-asuh/records/{id} (Admin)
    public function destroy($id): JsonResponse
    {
        $record = AnakAsuhRecord::findOrFail($id);
        $record->delete();

        return $this->successResponse(null, 'Catatan perkembangan berhasil dihapus');
    }
}
