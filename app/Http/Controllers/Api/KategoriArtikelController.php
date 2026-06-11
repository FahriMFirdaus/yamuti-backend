<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KategoriArtikel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KategoriArtikelController extends Controller
{
    public function index()
    {
        $kategoris = KategoriArtikel::all();
        return response()->json([
            'success' => true,
            'message' => 'Daftar kategori artikel berhasil diambil',
            'data' => $kategoris
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:kategori_artikels,slug'
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['nama_kategori']);
        }

        $kategori = KategoriArtikel::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori artikel berhasil ditambahkan',
            'data' => $kategori
        ], 201);
    }

    public function show(string $id)
    {
        $kategori = KategoriArtikel::findOrFail($id);
        return response()->json([
            'success' => true,
            'message' => 'Detail kategori artikel berhasil diambil',
            'data' => $kategori
        ]);
    }

    public function update(Request $request, string $id)
    {
        $kategori = KategoriArtikel::findOrFail($id);
        
        $validated = $request->validate([
            'nama_kategori' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|unique:kategori_artikels,slug,' . $id
        ]);

        if (isset($validated['nama_kategori']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['nama_kategori']);
        }

        $kategori->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori artikel berhasil diperbarui',
            'data' => $kategori
        ]);
    }

    public function destroy(string $id)
    {
        $kategori = KategoriArtikel::findOrFail($id);
        $kategori->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori artikel berhasil dihapus',
            'data' => null
        ]);
    }
}
