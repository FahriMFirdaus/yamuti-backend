<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artikel;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Jobs\PublishToMetaGraph;

class ArtikelController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $artikels = Artikel::with('penulis:id,name')->latest()->paginate(15);
        return $this->successResponse($artikels, 'Data artikel berhasil diambil');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
            'kategori' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048' // max 2MB
        ]);

        $thumbnailUrl = null;
        if ($request->hasFile('thumbnail')) {
            // Upload to S3
            $path = $request->file('thumbnail')->store('artikels', 's3');
            // Assuming S3 bucket is public, we can get the URL
            $thumbnailUrl = Storage::disk('s3')->url($path);
        }

        $artikel = Artikel::create([
            'judul' => $validated['judul'],
            'slug' => Str::slug($validated['judul']) . '-' . uniqid(),
            'konten' => $validated['konten'],
            'kategori' => $validated['kategori'] ?? 'Berita',
            'thumbnail_url' => $thumbnailUrl,
            'penulis_id' => $request->user()->id,
        ]);

        // Trigger Epic 3.4 (Publish to Meta Graph)
        PublishToMetaGraph::dispatch("Artikel Baru: {$artikel->judul}\n\nBaca selengkapnya di web kami!", $thumbnailUrl);

        return $this->successResponse($artikel, 'Artikel berhasil dibuat', 201);
    }

    public function show($id)
    {
        $artikel = Artikel::with('penulis:id,name')->findOrFail($id);
        return $this->successResponse($artikel, 'Detail artikel');
    }

    public function update(Request $request, $id)
    {
        $artikel = Artikel::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'sometimes|required|string|max:255',
            'konten' => 'sometimes|required|string',
            'kategori' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:2048'
        ]);

        if (isset($validated['judul'])) {
            $validated['slug'] = Str::slug($validated['judul']) . '-' . uniqid();
        }

        if ($request->hasFile('thumbnail')) {
            // Optionally delete old thumbnail from S3 here
            $path = $request->file('thumbnail')->store('artikels', 's3');
            $validated['thumbnail_url'] = Storage::disk('s3')->url($path);
            unset($validated['thumbnail']);
        }

        $artikel->update($validated);

        return $this->successResponse($artikel, 'Artikel berhasil diupdate');
    }

    public function destroy($id)
    {
        $artikel = Artikel::findOrFail($id);
        $artikel->delete();
        return $this->successResponse(null, 'Artikel berhasil dihapus');
    }
}
