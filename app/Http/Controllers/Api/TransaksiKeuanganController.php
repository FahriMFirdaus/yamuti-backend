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
        $jenisKas = $request->query('jenis_kas');
        $saldo = $this->transaksiService->getSaldo($jenisKas);
        return $this->successResponse(['jenis_kas' => $jenisKas ?? 'Semua', 'saldo' => $saldo], 'Saldo berhasil dihitung');
    }

    public function laporan(Request $request): JsonResponse
    {
        $bulan = $request->query('bulan', date('m'));
        $tahun = $request->query('tahun', date('Y'));
        $jenis = $request->query('jenis'); // pemasukan/pengeluaran -> Debit/Kredit

        $query = \App\Models\TransaksiKeuangan::whereYear('created_at', $tahun)
                    ->whereMonth('created_at', $bulan);
        
        if ($jenis === 'pemasukan') {
            $query->where('tipe_transaksi', 'Debit');
        } elseif ($jenis === 'pengeluaran') {
            $query->where('tipe_transaksi', 'Kredit');
        }

        $data = $query->latest()->get();

        return $this->successResponse($data, 'Laporan keuangan berhasil diambil');
    }
}
