<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnakAsuhRequest;
use App\Services\AnakAsuhService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnakAsuhController extends Controller
{
    use ApiResponse;

    protected AnakAsuhService $anakAsuhService;

    public function __construct(AnakAsuhService $anakAsuhService)
    {
        $this->anakAsuhService = $anakAsuhService;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $data = $this->anakAsuhService->getAllAnakAsuh((int)$perPage);
        return $this->successResponse($data, 'Data anak asuh berhasil diambil');
    }

    public function show($id): JsonResponse
    {
        $data = $this->anakAsuhService->getAnakAsuhById($id);
        return $this->successResponse($data, 'Detail anak asuh berhasil diambil');
    }

    public function store(AnakAsuhRequest $request): JsonResponse
    {
        $data = $this->anakAsuhService->createAnakAsuh($request->validated(), $request->user()->id);
        return $this->successResponse($data, 'Anak asuh berhasil ditambahkan', 201);
    }

    public function update(AnakAsuhRequest $request, $id): JsonResponse
    {
        $data = $this->anakAsuhService->updateAnakAsuh($id, $request->validated(), $request->user()->id);
        return $this->successResponse($data, 'Anak asuh berhasil diperbarui');
    }

    public function destroy($id): JsonResponse
    {
        $this->anakAsuhService->deleteAnakAsuh($id);
        return $this->successResponse(null, 'Anak asuh berhasil dihapus');
    }
}
