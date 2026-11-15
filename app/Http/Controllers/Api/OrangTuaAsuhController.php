<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrangTuaAsuh;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrangTuaAsuhController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = OrangTuaAsuh::with(['anakAsuh.records', 'user:id,name,email'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_donatur', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%")
                    ->orWhereHas('anakAsuh', function ($sub) use ($search) {
                        $sub->where('nama', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) $request->query('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->successResponse($data, 'Data orang tua asuh berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'nama_donatur' => 'required|string|max:255',
            'no_hp' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'anak_asuh_id' => 'required|exists:anak_asuhs,id',
            'nominal_bulanan' => 'required|numeric|min:10000',
            'status' => 'required|in:Aktif,Jeda,Selesai',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'hari_tagihan_per_bulan' => 'required|integer|min:1|max:31',
            'catatan_doa' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id ?? null;
        $orangTuaAsuh = OrangTuaAsuh::create($validated);

        return $this->successResponse($orangTuaAsuh->load('anakAsuh'), 'Orang tua asuh berhasil didaftarkan', 201);
    }

    public function show($id): JsonResponse
    {
        $data = OrangTuaAsuh::with(['anakAsuh', 'user'])->findOrFail($id);

        return $this->successResponse($data, 'Detail orang tua asuh berhasil diambil');
    }

    public function update(Request $request, $id): JsonResponse
    {
        $orangTuaAsuh = OrangTuaAsuh::findOrFail($id);

        $validated = $request->validate([
            'nama_donatur' => 'sometimes|string|max:255',
            'no_hp' => 'sometimes|string|max:30',
            'email' => 'nullable|email|max:255',
            'anak_asuh_id' => 'sometimes|exists:anak_asuhs,id',
            'nominal_bulanan' => 'sometimes|numeric|min:10000',
            'status' => 'sometimes|in:Aktif,Jeda,Selesai',
            'tanggal_mulai' => 'sometimes|date',
            'tanggal_selesai' => 'nullable|date',
            'hari_tagihan_per_bulan' => 'sometimes|integer|min:1|max:31',
            'catatan_doa' => 'nullable|string',
        ]);

        $validated['updated_by'] = $request->user()->id ?? null;
        $orangTuaAsuh->update($validated);

        return $this->successResponse($orangTuaAsuh->load('anakAsuh'), 'Data orang tua asuh berhasil diperbarui');
    }

    public function destroy($id): JsonResponse
    {
        $orangTuaAsuh = OrangTuaAsuh::findOrFail($id);
        $orangTuaAsuh->delete();

        return $this->successResponse(null, 'Data komitmen orang tua asuh berhasil dihapus');
    }
}
