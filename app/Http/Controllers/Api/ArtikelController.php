<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\PublishToMetaGraph;
use App\Models\Artikel;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class ArtikelController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $artikels = Artikel::with(['penulis:id,name', 'kategoriArtikel:id,nama_kategori,slug'])->latest()->paginate($perPage);

        return $this->successResponse($artikels, 'Data artikel berhasil diambil');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'konten' => 'required|string',
            'kategori_id' => 'nullable|uuid|exists:kategori_artikels,id',
            'thumbnail' => 'nullable|image|max:2048',
        ]);

        $thumbnailUrl = null;
        if ($request->hasFile('thumbnail')) {
            // Upload to S3
            $path = $request->file('thumbnail')->store('artikels', 's3');
            // Assuming S3 bucket is public, we can get the URL
            $thumbnailUrl = Storage::disk('s3')->url($path);
        }

        $kategoriId = $validated['kategori_id'] ?? null;
        if ($kategoriId === 'null' || $kategoriId === '') {
            $kategoriId = null;
        }

        $artikel = Artikel::create([
            'judul' => $validated['judul'],
            'slug' => Str::slug($validated['judul']).'-'.uniqid(),
            'konten' => Purifier::clean($validated['konten']),
            'kategori_id' => $kategoriId,
            'thumbnail_url' => $thumbnailUrl,
            'penulis_id' => $request->user()->id,
        ]);

        // Trigger Epic 3.4 (Publish to Meta Graph)
        PublishToMetaGraph::dispatch("Artikel Baru: {$artikel->judul}\n\nBaca selengkapnya di web kami!", $thumbnailUrl);

        return $this->successResponse($artikel, 'Artikel berhasil dibuat', 201);
    }

    public function show($id)
    {
        $artikel = Artikel::with(['penulis:id,name', 'kategoriArtikel:id,nama_kategori,slug'])
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();

        return $this->successResponse($artikel, 'Detail artikel');
    }

    public function update(Request $request, $id)
    {
        $artikel = Artikel::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'sometimes|required|string|max:255',
            'konten' => 'sometimes|required|string',
            'kategori_id' => 'nullable|uuid|exists:kategori_artikels,id',
            'thumbnail' => 'nullable|image|max:2048',
        ]);

        if (array_key_exists('kategori_id', $validated)) {
            if ($validated['kategori_id'] === 'null' || $validated['kategori_id'] === '') {
                $validated['kategori_id'] = null;
            }
        }

        if (isset($validated['judul'])) {
            $validated['slug'] = Str::slug($validated['judul']).'-'.uniqid();
        }

        if ($request->hasFile('thumbnail')) {
            // Optionally delete old thumbnail from S3 here
            $path = $request->file('thumbnail')->store('artikels', 's3');
            $validated['thumbnail_url'] = Storage::disk('s3')->url($path);
            unset($validated['thumbnail']);
        }

        if (isset($validated['konten'])) {
            $validated['konten'] = Purifier::clean($validated['konten']);
        }

        $artikel->update($validated);

        return $this->successResponse($artikel, 'Artikel berhasil diupdate');
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120', // max 5MB
        ]);

        if ($request->hasFile('image')) {
            $hasS3 = ! empty(config('filesystems.disks.s3.bucket'));
            if ($hasS3) {
                $path = $request->file('image')->store('artikels/content', 's3');
                $url = Storage::disk('s3')->url($path);
            } else {
                $path = $request->file('image')->store('artikels/content', 'public');
                $url = asset(Storage::url($path));
            }

            return $this->successResponse(['url' => $url], 'Foto berhasil diunggah');
        }

        return $this->errorResponse('Tidak ada berkas yang diunggah', 400);
    }

    public function destroy($id)
    {
        $artikel = Artikel::findOrFail($id);
        $artikel->delete();

        return $this->successResponse(null, 'Artikel berhasil dihapus');
    }
}
