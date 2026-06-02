<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MutasiBarangRequest;
use App\Services\MutasiBarangService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class MutasiBarangController extends Controller
{
    use ApiResponse;

    protected MutasiBarangService $mutasiService;

    public function __construct(MutasiBarangService $mutasiService)
    {
        $this->mutasiService = $mutasiService;
    }

    public function index(Request $request, $inventarisId): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $data = $this->mutasiService->getRiwayatMutasi($inventarisId, (int)$perPage);
        return $this->successResponse($data, 'Riwayat mutasi barang berhasil diambil');
    }

    public function store(MutasiBarangRequest $request, $inventarisId): JsonResponse
    {
        try {
            $data = $this->mutasiService->createMutasi($inventarisId, $request->validated(), $request->user()->id);
            return $this->successResponse($data, 'Mutasi barang berhasil dicatat', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
