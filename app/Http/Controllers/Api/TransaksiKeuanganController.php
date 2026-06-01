<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransaksiKeuanganRequest;
use App\Services\TransaksiKeuanganService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransaksiKeuanganController extends Controller
{
    use ApiResponse;

    protected TransaksiKeuanganService $transaksiService;

    public function __construct(TransaksiKeuanganService $transaksiService)
    {
        $this->transaksiService = $transaksiService;
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->transaksiService->getAllTransaksi((int)$request->query('per_page', 15));
        return $this->successResponse($data, 'Data transaksi berhasil diambil');
    }

    public function store(TransaksiKeuanganRequest $request): JsonResponse
    {
        $data = $this->transaksiService->createTransaksi($request->validated(), $request->user()->id);
        return $this->successResponse($data, 'Transaksi berhasil dicatat', 201);
    }

    public function saldo(Request $request): JsonResponse
    {
        $jenisKas = $request->query('jenis_kas', 'Cabang');
        $saldo = $this->transaksiService->getSaldo($jenisKas);
        return $this->successResponse(['jenis_kas' => $jenisKas, 'saldo' => $saldo], 'Saldo berhasil dihitung');
    }
}
