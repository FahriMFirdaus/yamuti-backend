<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnakAsuh;
use App\Models\Donasi;
use App\Models\Kunjungan;
use App\Services\TransaksiKeuanganService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponse;

    protected TransaksiKeuanganService $transaksiService;

    public function __construct(TransaksiKeuanganService $transaksiService)
    {
        $this->transaksiService = $transaksiService;
    }

    public function summary(): JsonResponse
    {
        $totalAnakAsuh = AnakAsuh::where('status', 'Aktif')->count();
        
        $totalDonasiBulanIni = Donasi::where('status', 'PAID')
            ->whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->sum('gross_amount');
            
        $totalKunjunganMenunggu = Kunjungan::where('status', 'PENDING')->count();
        
        $saldoKasTerkini = $this->transaksiService->getSaldo('Cabang'); // Atau total dari semua kas

        $data = [
            'total_anak_asuh' => $totalAnakAsuh,
            'total_donasi_bulan_ini' => $totalDonasiBulanIni,
            'total_kunjungan_menunggu' => $totalKunjunganMenunggu,
            'saldo_kas_terkini' => $saldoKasTerkini
        ];

        return $this->successResponse($data, 'Data summary dashboard berhasil diambil');
    }
}
