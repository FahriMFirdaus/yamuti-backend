<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KaryawanController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of employees (karyawan & pengasuh).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Karyawan::query();

        // Search by query string (name, NIP, NIK, jabatan, divisi, no_hp)
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('jabatan', 'like', "%{$search}%")
                    ->orWhere('divisi', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        // Filter by Tipe SDM
        if ($tipe = $request->input('tipe_sdm')) {
            $query->where('tipe_sdm', $tipe);
        }

        // Filter by Divisi
        if ($divisi = $request->input('divisi')) {
            $query->where('divisi', $divisi);
        }

        // Filter by Status Aktif
        if ($statusAktif = $request->input('status_aktif')) {
            $query->where('status_aktif', $statusAktif);
        }

        // Filter by Status Kepegawaian
        if ($statusKepegawaian = $request->input('status_kepegawaian')) {
            $query->where('status_kepegawaian', $statusKepegawaian);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'nama_lengkap');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 15);
        if ($perPage === 'all') {
            $data = $query->get();
        } else {
            $data = $query->paginate((int) $perPage);
        }

        return $this->successResponse($data, 'Data karyawan berhasil diambil');
    }

    /**
     * Summary statistics for SDM.
     */
    public function summary(): JsonResponse
    {
        $summary = [
            'total' => Karyawan::count(),
            'aktif' => Karyawan::where('status_aktif', 'Aktif')->count(),
            'nonaktif' => Karyawan::where('status_aktif', 'Nonaktif')->count(),
            'cuti' => Karyawan::where('status_aktif', 'Cuti')->count(),
            'pengasuh' => Karyawan::where('tipe_sdm', 'Pengasuh/Ustadz')->count(),
            'staf' => Karyawan::where('tipe_sdm', 'Staf/Amil')->count(),
            'pengasuh_aktif' => Karyawan::where('tipe_sdm', 'Pengasuh/Ustadz')->where('status_aktif', 'Aktif')->count(),
            'staf_aktif' => Karyawan::where('tipe_sdm', 'Staf/Amil')->where('status_aktif', 'Aktif')->count(),
        ];

        return $this->successResponse($summary, 'Summary data karyawan berhasil diambil');
    }

    /**
     * Store a newly created employee record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nip' => 'nullable|string|max:30|unique:karyawans,nip',
            'nik' => 'nullable|string|max:16',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'no_hp' => 'nullable|string|max:25',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
            'tipe_sdm' => 'required|in:Pengasuh/Ustadz,Staf/Amil',
            'jabatan' => 'required|string|max:100',
            'divisi' => 'nullable|string|max:100',
            'status_kepegawaian' => 'nullable|in:Tetap,Kontrak,Honorer,Magang',
            'status_aktif' => 'nullable|in:Aktif,Nonaktif,Cuti',
            'tanggal_bergabung' => 'nullable|date',
            'tanggal_berhenti' => 'nullable|date',
            'nama_bank' => 'nullable|string|max:50',
            'nomor_rekening' => 'nullable|string|max:50',
            'atas_nama_rekening' => 'nullable|string|max:100',
            'gaji_pokok_standar' => 'nullable|numeric|min:0',
            'tunjangan_tetap_standar' => 'nullable|numeric|min:0',
            'foto' => 'nullable|string',
            'catatan' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        // Auto-generate NIP if not provided (Format: YMT-YYYY-XXXX)
        if (empty($validated['nip'])) {
            $year = date('Y');
            $count = Karyawan::whereYear('created_at', $year)->count() + 1;
            do {
                $nip = sprintf('YMT-%s-%04d', $year, $count);
                $exists = Karyawan::where('nip', $nip)->exists();
                if ($exists) {
                    $count++;
                }
            } while ($exists);
            $validated['nip'] = $nip;
        }

        $validated['created_by'] = $request->user()?->id;

        $karyawan = Karyawan::create($validated);

        return $this->successResponse($karyawan, 'Karyawan berhasil ditambahkan', 201);
    }

    /**
     * Display the specified employee along with recent payroll history.
     */
    public function show($id): JsonResponse
    {
        $targetId = $id instanceof Karyawan ? $id->id : $id;

        $karyawan = Karyawan::with([
            'payrolls' => function ($q) {
                $q->orderBy('periode_tahun', 'desc')
                    ->orderBy('periode_bulan', 'desc')
                    ->limit(12);
            },
            'user',
            'creator',
        ])->findOrFail($targetId);

        return $this->successResponse($karyawan, 'Detail karyawan berhasil diambil');
    }

    /**
     * Update the specified employee record.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $targetId = $id instanceof Karyawan ? $id->id : $id;
        $karyawan = Karyawan::findOrFail($targetId);

        $validated = $request->validate([
            'nama_lengkap' => 'sometimes|required|string|max:255',
            'nip' => 'nullable|string|max:30|unique:karyawans,nip,' . $targetId,
            'nik' => 'nullable|string|max:16',
            'jenis_kelamin' => 'sometimes|required|in:Laki-laki,Perempuan',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'no_hp' => 'nullable|string|max:25',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
            'tipe_sdm' => 'sometimes|required|in:Pengasuh/Ustadz,Staf/Amil',
            'jabatan' => 'sometimes|required|string|max:100',
            'divisi' => 'nullable|string|max:100',
            'status_kepegawaian' => 'nullable|in:Tetap,Kontrak,Honorer,Magang',
            'status_aktif' => 'nullable|in:Aktif,Nonaktif,Cuti',
            'tanggal_bergabung' => 'nullable|date',
            'tanggal_berhenti' => 'nullable|date',
            'nama_bank' => 'nullable|string|max:50',
            'nomor_rekening' => 'nullable|string|max:50',
            'atas_nama_rekening' => 'nullable|string|max:100',
            'gaji_pokok_standar' => 'nullable|numeric|min:0',
            'tunjangan_tetap_standar' => 'nullable|numeric|min:0',
            'foto' => 'nullable|string',
            'catatan' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $validated['updated_by'] = $request->user()?->id;

        $karyawan->update($validated);

        return $this->successResponse($karyawan, 'Data karyawan berhasil diperbarui');
    }

    /**
     * Remove the specified employee record (soft delete).
     */
    public function destroy($id): JsonResponse
    {
        $targetId = $id instanceof Karyawan ? $id->id : $id;
        $karyawan = Karyawan::findOrFail($targetId);
        $karyawan->delete();

        return $this->successResponse(null, 'Karyawan berhasil dinonaktifkan/dihapus');
    }
}
