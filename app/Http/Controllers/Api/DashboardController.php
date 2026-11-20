<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnakAsuh;
use App\Models\Donasi;
use App\Models\Kampanye;
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
        $totalProgramAktif = Kampanye::whereNotIn('status', ['Draft', 'draft', 'Dibatalkan', 'dibatalkan', 'Selesai'])->count();

        // 5 Donasi Terbaru (PAID)
        $donasiTerbaru = Donasi::with(['kampanye:id,judul', 'user:id,name'])
            ->where('status', 'PAID')
            ->latest()
            ->take(5)
            ->get(['id', 'user_id', 'nama_donatur', 'gross_amount', 'created_at', 'kampanye_id', 'is_anonymous'])
            ->map(function ($d) {
                $donorName = $d->is_anonymous
                    ? 'Hamba Allah'
                    : ($d->nama_donatur ?? $d->user?->name ?? 'Hamba Allah');

                return [
                    'id' => $d->id,
                    'donor_name' => $donorName,
                    'gross_amount' => (float) $d->gross_amount,
                    'created_at' => $d->created_at ? $d->created_at->toISOString() : null,
                    'program_name' => $d->kampanye?->judul ?? 'Donasi Umum',
                ];
            });

        // Data Pemasukan 7 Hari Terakhir (Dioptimalkan dengan 1 single query agregasi range tanggal)
        $sevenDaysAgo = now()->subDays(6)->startOfDay();
        $donasiAggregates = Donasi::where('status', 'PAID')
            ->where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(created_at) as tgl, SUM(gross_amount) as total')
            ->groupBy('tgl')
            ->pluck('total', 'tgl')
            ->toArray();

        $chartHarian = [];
        for ($i = 6; $i >= 0; $i--) {
            $carbon = now()->subDays($i);
            $date = $carbon->format('Y-m-d');
            $dayLabel = $carbon->translatedFormat('D');

            $chartHarian[] = [
                'tanggal' => $date,
                'hari' => $dayLabel,
                'nominal' => (float) ($donasiAggregates[$date] ?? 0),
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
