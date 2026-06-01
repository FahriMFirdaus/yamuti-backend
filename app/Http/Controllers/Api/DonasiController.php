<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonasiRequest;
use App\Services\DonasiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonasiController extends Controller
{
    use ApiResponse;

    protected DonasiService $donasiService;

    public function __construct(DonasiService $donasiService)
    {
        $this->donasiService = $donasiService;
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->donasiService->getAllDonasi((int)$request->query('per_page', 15));
        return $this->successResponse($data, 'Data donasi berhasil diambil');
    }

    public function show($id): JsonResponse
    {
        $data = $this->donasiService->getDonasiById($id);
        return $this->successResponse($data, 'Detail donasi berhasil diambil');
    }

    public function store(DonasiRequest $request): JsonResponse
    {
        // Donatur bisa saja tamu yang tidak login, jadi id bersifat opsional
        $userId = $request->user('sanctum')?->id;
        $data = $this->donasiService->createDonasi($request->validated(), $userId);
        return $this->successResponse($data, 'Donasi berhasil dicatat', 201);
    }

    public function markAsPaid($id): JsonResponse
    {
        // Fitur simulasi atau webhook dari Payment Gateway
        $data = $this->donasiService->updateStatus($id, 'PAID');
        return $this->successResponse($data, 'Donasi berhasil dibayar dan dana telah dialokasikan (Split 10-90)');
    }
}
