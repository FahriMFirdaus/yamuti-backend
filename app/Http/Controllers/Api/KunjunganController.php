<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KunjunganRequest;
use App\Services\KunjunganService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KunjunganController extends Controller
{
    use ApiResponse;

    protected KunjunganService $kunjunganService;

    public function __construct(KunjunganService $kunjunganService)
    {
        $this->kunjunganService = $kunjunganService;
    }

    // Endpoint Publik
    public function store(KunjunganRequest $request): JsonResponse
    {
        $data = $this->kunjunganService->requestKunjungan($request->validated());
        return $this->successResponse($data, 'Permintaan kunjungan berhasil diajukan, menunggu persetujuan admin', 201);
    }

    // Endpoint Terlindungi
    public function index(Request $request): JsonResponse
    {
        $data = $this->kunjunganService->getAllKunjungan((int)$request->query('per_page', 15));
        return $this->successResponse($data, 'Data kunjungan berhasil diambil');
    }

    public function show($id): JsonResponse
    {
        $data = $this->kunjunganService->getKunjunganById($id);
        return $this->successResponse($data, 'Detail kunjungan berhasil diambil');
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $status = strtoupper($request->input('status')); // APPROVED, REJECTED, COMPLETED
        $adminId = $request->user()->id;

        if ($status === 'APPROVED') {
            $data = $this->kunjunganService->approveKunjungan($id, $adminId);
            return $this->successResponse($data, 'Kunjungan disetujui');
        } elseif ($status === 'REJECTED') {
            $data = $this->kunjunganService->rejectKunjungan($id, $adminId);
            return $this->successResponse($data, 'Kunjungan ditolak');
        } else {
            // Jika ada status COMPLETED dll, bisa diarahkan ke update standar
            // Untuk sementara kita panggil fungsi approve/reject, atau update model langsung jika ada fungsi di service
            // Untuk COMPLETED kita harus update manual jika belum ada di service:
            $kunjungan = \App\Models\Kunjungan::findOrFail($id);
            $kunjungan->update(['status' => $status]);
            return $this->successResponse($kunjungan, "Kunjungan diperbarui menjadi $status");
        }
    }

    public function riwayat(Request $request): JsonResponse
    {
        $noHp = $request->user()->no_hp;
        if (!$noHp) {
            return $this->successResponse([], 'Nomor telepon (no_hp) pada profil Anda belum diisi, riwayat kunjungan tidak dapat dilacak.', 200);
        }
        $data = \App\Models\Kunjungan::where('no_whatsapp', $noHp)->latest()->paginate(15);
        return $this->successResponse($data, 'Riwayat kunjungan berhasil diambil');
    }
}
