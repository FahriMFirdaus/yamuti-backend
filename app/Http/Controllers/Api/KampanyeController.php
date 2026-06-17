<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KampanyeRequest;
use App\Models\Kampanye;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class KampanyeController extends Controller
{
    use ApiResponse;

    // Menampilkan daftar kampanye (bisa diakses publik juga)
    public function index(Request $request): JsonResponse
    {
        $query = Kampanye::query();

        // Jika hanya ingin melihat yang aktif (untuk publik)
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        // Tampilkan juga dengan total donasi yang sudah terkumpul
        $kampanyes = $query->withSum(['donasi' => function ($q) {
            $q->where('status', 'PAID');
        }], 'gross_amount')->latest()->paginate($request->query('per_page', 15));

        return $this->successResponse($kampanyes, 'Daftar kampanye berhasil diambil');
    }

    public function store(KampanyeRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['judul']) . '-' . time();

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('kampanye', 'public');
        }

        $kampanye = Kampanye::create($validated);

        return $this->successResponse($kampanye, 'Kampanye baru berhasil dibuat', 201);
    }

    public function show($id): JsonResponse
    {
        // Ambil kampanye berdasarkan ID atau Slug
        $kampanye = Kampanye::withSum(['donasi' => function ($q) {
            $q->where('status', 'PAID');
        }], 'gross_amount')->where('id', $id)->orWhere('slug', $id)->firstOrFail();

        return $this->successResponse($kampanye, 'Detail kampanye berhasil diambil');
    }

    public function update(KampanyeRequest $request, $id): JsonResponse
    {
        $kampanye = Kampanye::findOrFail($id);
        $validated = $request->validated();

        if ($request->has('judul') && $request->judul !== $kampanye->judul) {
            $validated['slug'] = Str::slug($validated['judul']) . '-' . time();
        }

        if ($request->hasFile('thumbnail')) {
            if ($kampanye->thumbnail) {
                Storage::disk('public')->delete($kampanye->thumbnail);
            }
            $validated['thumbnail'] = $request->file('thumbnail')->store('kampanye', 'public');
        }

        $kampanye->update($validated);

        return $this->successResponse($kampanye, 'Kampanye berhasil diperbarui');
    }

    public function destroy($id): JsonResponse
    {
        $kampanye = Kampanye::findOrFail($id);
        
        if ($kampanye->thumbnail) {
            Storage::disk('public')->delete($kampanye->thumbnail);
        }
        
        $kampanye->delete();

        return $this->successResponse(null, 'Kampanye berhasil dihapus');
    }
}
