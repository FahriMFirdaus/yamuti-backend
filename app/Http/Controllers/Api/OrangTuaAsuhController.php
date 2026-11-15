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

    /**
     * Endpoint khusus bagi user/donatur terotentikasi untuk melihat data komitmen POTA miliknya sendiri.
     */
    public function saya(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = OrangTuaAsuh::with(['anakAsuh:id,nama,nik,jenis_kelamin,tingkat_pendidikan,nama_sekolah,status,foto_identitas'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if (! empty($user->email)) {
                    $q->orWhere('email', $user->email);
                }
                if (! empty($user->no_hp)) {
                    $q->orWhere('no_hp', $user->no_hp);
                }
            })
            ->latest()
            ->get();

        return $this->successResponse($data, 'Data anak asuh binaan berhasil diambil');
    }

    /**
     * Endpoint bagi user/donatur terotentikasi untuk mendaftarkan komitmen POTA baru secara mandiri.
     * Mendukung pendaftaran 1 anak maupun banyak anak (multi-select) sekaligus.
     */
    public function daftarMandiri(Request $request): JsonResponse
    {
        $user = $request->user();

        // Cek apakah request berupa array 'items' (multi-anak) atau single
        if ($request->has('items') && is_array($request->input('items'))) {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.anak_asuh_id' => 'required|exists:anak_asuhs,id',
                'items.*.nominal_bulanan' => 'required|numeric|min:50000',
                'items.*.hari_tagihan_per_bulan' => 'required|integer|min:1|max:31',
                'items.*.catatan_doa' => 'nullable|string|max:500',
            ]);

            $created = [];
            \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $user, &$created) {
                foreach ($validated['items'] as $item) {
                    $exists = OrangTuaAsuh::where('anak_asuh_id', $item['anak_asuh_id'])
                        ->where('status', 'Aktif')
                        ->where(function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                            if (! empty($user->email)) {
                                $q->orWhere('email', $user->email);
                            }
                        })
                        ->exists();

                    if (! $exists) {
                        $pota = OrangTuaAsuh::create([
                            'user_id' => $user->id,
                            'nama_donatur' => $user->name,
                            'no_hp' => $user->no_hp ?: '-',
                            'email' => $user->email,
                            'anak_asuh_id' => $item['anak_asuh_id'],
                            'nominal_bulanan' => $item['nominal_bulanan'],
                            'status' => 'Aktif',
                            'tanggal_mulai' => now()->toDateString(),
                            'hari_tagihan_per_bulan' => $item['hari_tagihan_per_bulan'],
                            'catatan_doa' => $item['catatan_doa'] ?? null,
                            'created_by' => $user->id,
                        ]);
                        $created[] = $pota;
                    }
                }
            });

            return $this->successResponse($created, 'Alhamdulillah! Komitmen Orang Tua Asuh untuk '.count($created).' ananda berhasil didaftarkan.', 201);
        }

        $validated = $request->validate([
            'anak_asuh_id' => 'required|exists:anak_asuhs,id',
            'nominal_bulanan' => 'required|numeric|min:50000',
            'hari_tagihan_per_bulan' => 'required|integer|min:1|max:31',
            'catatan_doa' => 'nullable|string|max:500',
        ]);

        // Cek apakah donatur ini sudah menjadi orang tua asuh untuk anak ini dan masih aktif
        $exists = OrangTuaAsuh::where('anak_asuh_id', $validated['anak_asuh_id'])
            ->where('status', 'Aktif')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if (! empty($user->email)) {
                    $q->orWhere('email', $user->email);
                }
            })
            ->exists();

        if ($exists) {
            return $this->errorResponse('Anda sudah terdaftar sebagai Orang Tua Asuh aktif untuk ananda ini.', 422);
        }

        $pota = OrangTuaAsuh::create([
            'user_id' => $user->id,
            'nama_donatur' => $user->name,
            'no_hp' => $user->no_hp ?: '-',
            'email' => $user->email,
            'anak_asuh_id' => $validated['anak_asuh_id'],
            'nominal_bulanan' => $validated['nominal_bulanan'],
            'status' => 'Aktif',
            'tanggal_mulai' => now()->toDateString(),
            'hari_tagihan_per_bulan' => $validated['hari_tagihan_per_bulan'],
            'catatan_doa' => $validated['catatan_doa'] ?? null,
            'created_by' => $user->id,
        ]);

        return $this->successResponse($pota->load('anakAsuh'), 'Alhamdulillah! Komitmen Orang Tua Asuh berhasil didaftarkan.', 201);
    }

    /**
     * Endpoint untuk mengambil daftar anak asuh yang tersedia untuk dipilih oleh donatur.
     */
    public function availableAnakAsuh(Request $request): JsonResponse
    {
        // Ambil anak asuh aktif yang belum memiliki orang tua asuh aktif beserta bio & foto
        $data = \App\Models\AnakAsuh::where('status', 'Aktif')
            ->select('id', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'tingkat_pendidikan', 'nama_sekolah', 'foto_identitas', 'keterangan', 'status_keluarga')
            ->whereDoesntHave('orangTuaAsuh', function ($q) {
                $q->where('status', 'Aktif');
            })
            ->latest()
            ->take(30)
            ->get();

        return $this->successResponse($data, 'Daftar calon anak asuh binaan berhasil diambil');
    }
}
