<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventarisRequest;
use App\Services\InventarisService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventarisController extends Controller
{
    use ApiResponse;

    protected InventarisService $inventarisService;

    public function __construct(InventarisService $inventarisService)
    {
        $this->inventarisService = $inventarisService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $data = $this->inventarisService->getAllInventaris((int)$perPage);
        return $this->successResponse($data, 'Data inventaris berhasil diambil');
    }

    public function show($id): JsonResponse
    {
        $data = $this->inventarisService->getInventarisById($id);
        return $this->successResponse($data, 'Detail inventaris berhasil diambil');
    }

    public function store(InventarisRequest $request): JsonResponse
    {
        $data = $this->inventarisService->createInventaris($request->validated(), $request->user()->id);
        return $this->successResponse($data, 'Inventaris berhasil ditambahkan', 201);
    }

    public function update(InventarisRequest $request, $id): JsonResponse
    {
        $data = $this->inventarisService->updateInventaris($id, $request->validated(), $request->user()->id);
        return $this->successResponse($data, 'Inventaris berhasil diperbarui');
    }

    public function destroy($id): JsonResponse
    {
        $this->inventarisService->deleteInventaris($id);
        return $this->successResponse(null, 'Inventaris berhasil dihapus');
    }
}
