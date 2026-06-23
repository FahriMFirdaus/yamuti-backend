<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Galeri;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Jobs\PublishToMetaGraph;

class GaleriController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $galeris = Galeri::with('pengunggah:id,name')->latest()->paginate(15);
        return $this->successResponse($galeris, 'Data galeri berhasil diambil');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'file' => 'required|image|max:5120' // max 5MB
        ]);

        $fileUrl = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('galeris', 's3');
            $fileUrl = Storage::disk('s3')->url($path);
        }

        $galeri = Galeri::create([
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'file_url' => $fileUrl,
            'diunggah_oleh' => $request->user()->id,
        ]);

        // Trigger Epic 3.4
        PublishToMetaGraph::dispatch("Foto Baru: {$galeri->judul}\n{$galeri->deskripsi}", $fileUrl);

        return $this->successResponse($galeri, 'Foto galeri berhasil diunggah', 201);
    }

    public function show($id)
    {
        $galeri = Galeri::with('pengunggah:id,name')->findOrFail($id);
        return $this->successResponse($galeri, 'Detail galeri');
    }

    public function destroy($id)
    {
        $galeri = Galeri::findOrFail($id);
        $galeri->delete();
        return $this->successResponse(null, 'Foto galeri berhasil dihapus');
    }

    public function update(Request $request, $id)
    {
        $galeri = Galeri::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'sometimes|required|string|max:255',
            'deskripsi' => 'nullable|string',
            'file' => 'nullable|image|max:5120'
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('galeris', 's3');
            $validated['file_url'] = Storage::disk('s3')->url($path);
        }

        $galeri->update($validated);

        return $this->successResponse($galeri, 'Data galeri berhasil diperbarui');
    }
}
