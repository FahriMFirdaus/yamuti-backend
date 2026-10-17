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
        
        $saldoKasTerkini = $this->transaksiService->getSaldo();

        // Total Program Kampanye Aktif
        $totalProgramAktif = \App\Models\Kampanye::whereNotIn('status', ['Draft', 'draft', 'Dibatalkan', 'dibatalkan', 'Selesai'])->count();

        // 5 Donasi Terbaru (PAID)
        $donasiTerbaru = Donasi::where('status', 'PAID')
            ->latest()
            ->take(5)
            ->get(['id', 'donor_name', 'gross_amount', 'created_at', 'program_name']);

        // Data Pemasukan 7 Hari Terakhir
        $chartHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $carbon = now()->subDays($i);
            $date = $carbon->format('Y-m-d');
            $dayLabel = $carbon->translatedFormat('D');
            
            $nominalHarian = Donasi::where('status', 'PAID')
                ->whereDate('created_at', $date)
                ->sum('gross_amount');

            $chartHarian[] = [
                'tanggal' => $date,
                'hari' => $dayLabel,
                'nominal' => (float) $nominalHarian,
            ];
        }

        $data = [
            'total_anak_asuh' => $totalAnakAsuh,
            'total_donasi_bulan_ini' => (float) $totalDonasiBulanIni,
            'total_kunjungan_menunggu' => $totalKunjunganMenunggu,
            'saldo_kas_terkini' => (float) $saldoKasTerkini,
            'total_program_aktif' => $totalProgramAktif,
            'donasi_terbaru' => $donasiTerbaru,
            'chart_harian' => $chartHarian,
        ];

        return $this->successResponse($data, 'Data summary dashboard berhasil diambil');
    }
}
