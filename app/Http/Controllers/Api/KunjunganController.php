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

    public function approve(Request $request, $id): JsonResponse
    {
        $data = $this->kunjunganService->approveKunjungan($id, $request->user()->id);
        return $this->successResponse($data, 'Kunjungan disetujui');
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $data = $this->kunjunganService->rejectKunjungan($id, $request->user()->id);
        return $this->successResponse($data, 'Kunjungan ditolak');
    }
}
