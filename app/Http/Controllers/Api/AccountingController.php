<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JurnalItem;
use App\Models\JurnalUmum;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    use ApiResponse;

    /**
     * Daftar Bagan Akun (Chart of Accounts)
     */
    public function coa(Request $request): JsonResponse
    {
        $tipe = $request->query('tipe');
        $query = ChartOfAccount::orderBy('kode_akun', 'asc');

        if ($tipe) {
            $query->where('tipe_akun', $tipe);
        }

        return $this->successResponse($query->get(), 'Daftar Bagan Akun berhasil diambil');
    }

    /**
     * Jurnal Umum dengan Pagination & Filter Tanggal
     */
    public function jurnal(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $search = $request->query('q');

        $query = JurnalUmum::with(['items.account', 'creator:id,name'])
            ->latest('tanggal_transaksi')
            ->latest('created_at');

        if ($startDate) {
            $query->whereDate('tanggal_transaksi', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('tanggal_transaksi', '<=', $endDate);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_referensi', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $data = $query->paginate($perPage);

        return $this->successResponse($data, 'Data Jurnal Umum berhasil diambil');
    }

    /**
     * Catat Jurnal Penyesuaian / Jurnal Manual Baru (Debit & Kredit Seimbang)
     */
    public function storeJurnalManual(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal_transaksi' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'items' => 'required|array|min:2',
            'items.*.chart_of_account_id' => 'required|uuid|exists:chart_of_accounts,id',
            'items.*.debit' => 'required|numeric|min:0',
            'items.*.kredit' => 'required|numeric|min:0',
            'items.*.memo' => 'nullable|string|max:255',
        ]);

        $totalDebit = 0;
        $totalKredit = 0;

        foreach ($validated['items'] as $item) {
            $totalDebit += (float) $item['debit'];
            $totalKredit += (float) $item['kredit'];
        }

        if (round($totalDebit, 2) !== round($totalKredit, 2)) {
            return $this->errorResponse('Total Debit dan Total Kredit harus seimbang (Balance).', 422, [
                'total_debit' => $totalDebit,
                'total_kredit' => $totalKredit,
                'selisih' => abs($totalDebit - $totalKredit),
            ]);
        }

        if ($totalDebit <= 0) {
            return $this->errorResponse('Nominal jurnal tidak boleh bernilai nol.', 422);
        }

        $jurnal = DB::transaction(function () use ($validated, $totalDebit, $request) {
            $nomorRef = 'JU-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $jurnalUmum = JurnalUmum::create([
                'nomor_referensi' => $nomorRef,
                'tanggal_transaksi' => $validated['tanggal_transaksi'],
                'keterangan' => $validated['keterangan'],
                'tipe_referensi' => 'manual',
                'referensi_id' => null,
                'total_debit' => $totalDebit,
                'total_kredit' => $totalDebit,
                'created_by' => $request->user()?->id,
            ]);

            foreach ($validated['items'] as $item) {
                JurnalItem::create([
                    'jurnal_umum_id' => $jurnalUmum->id,
                    'chart_of_account_id' => $item['chart_of_account_id'],
                    'debit' => (float) $item['debit'],
                    'kredit' => (float) $item['kredit'],
                    'memo' => $item['memo'] ?? null,
                ]);
            }

            return $jurnalUmum->load('items.account');
        });

        return $this->successResponse($jurnal, 'Jurnal penyesuaian manual berhasil dicatat', 201);
    }

    /**
     * Buku Besar (General Ledger) per Akun
     */
    public function bukuBesar(Request $request): JsonResponse
    {
        $accountId = $request->query('account_id');
        $startDate = $request->query('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfYear()->toDateString());

        if (! $accountId) {
            $firstAccount = ChartOfAccount::orderBy('kode_akun', 'asc')->first();
            $accountId = $firstAccount ? $firstAccount->id : null;
        }

        if (! $accountId) {
            return $this->errorResponse('Tidak ada akun yang ditemukan', 404);
        }

        $account = ChartOfAccount::findOrFail($accountId);

        // Hitung Saldo Awal (Opening Balance) sebelum $startDate untuk akun riil (Aset, Kewajiban, Aset Neto)
        $openingBalance = 0;
        if (in_array($account->tipe_akun, ['Aset', 'Kewajiban', 'Aset Neto'])) {
            $priorDebit = JurnalItem::where('chart_of_account_id', $accountId)
                ->whereHas('jurnal', function ($q) use ($startDate) {
                    $q->whereDate('tanggal_transaksi', '<', $startDate);
                })->sum('debit');

            $priorKredit = JurnalItem::where('chart_of_account_id', $accountId)
                ->whereHas('jurnal', function ($q) use ($startDate) {
                    $q->whereDate('tanggal_transaksi', '<', $startDate);
                })->sum('kredit');

            if ($account->saldo_normal === 'Debit') {
                $openingBalance = $priorDebit - $priorKredit;
            } else {
                $openingBalance = $priorKredit - $priorDebit;
            }
        }

        $items = JurnalItem::with('jurnal')
            ->where('chart_of_account_id', $accountId)
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereDate('tanggal_transaksi', '>=', $startDate)
                    ->whereDate('tanggal_transaksi', '<=', $endDate);
            })
            ->join('jurnal_umums', 'jurnal_items.jurnal_umum_id', '=', 'jurnal_umums.id')
            ->orderBy('jurnal_umums.tanggal_transaksi', 'asc')
            ->select('jurnal_items.*')
            ->get();

        $runningBalance = $openingBalance;
        $ledger = $items->map(function ($item) use (&$runningBalance, $account) {
            if ($account->saldo_normal === 'Debit') {
                $runningBalance += ($item->debit - $item->kredit);
            } else {
                $runningBalance += ($item->kredit - $item->debit);
            }

            return [
                'id' => $item->id,
                'tanggal' => $item->jurnal->tanggal_transaksi->format('Y-m-d'),
                'nomor_referensi' => $item->jurnal->nomor_referensi,
                'keterangan' => $item->memo ?: $item->jurnal->keterangan,
                'debit' => $item->debit,
                'kredit' => $item->kredit,
                'saldo' => $runningBalance,
            ];
        });

        return $this->successResponse([
            'account' => $account,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'entries' => $ledger,
            'ending_balance' => $runningBalance,
        ], 'Buku Besar berhasil diambil');
    }

    /**
     * Laporan Posisi Keuangan / Neraca
     */
    public function neraca(Request $request): JsonResponse
    {
        $asOfDate = $request->query('as_of', now()->toDateString());

        $accounts = ChartOfAccount::whereIn('tipe_akun', ['Aset', 'Kewajiban', 'Aset Neto'])
            ->orderBy('kode_akun', 'asc')
            ->get();

        // Optimized single grouped query for all journal item sums up to $asOfDate
        $aggregatedSums = JurnalItem::selectRaw('chart_of_account_id, SUM(debit) as total_debit, SUM(kredit) as total_kredit')
            ->whereHas('jurnal', function ($q) use ($asOfDate) {
                $q->whereDate('tanggal_transaksi', '<=', $asOfDate);
            })
            ->groupBy('chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        $aset = [];
        $kewajiban = [];
        $asetNeto = [];

        $totalAset = 0;
        $totalKewajiban = 0;
        $totalAsetNeto = 0;

        foreach ($accounts as $acc) {
            $sums = $aggregatedSums->get($acc->id);
            $sumDebit = $sums ? (float) $sums->total_debit : 0.0;
            $sumKredit = $sums ? (float) $sums->total_kredit : 0.0;

            $balance = $acc->saldo_normal === 'Debit' ? ($sumDebit - $sumKredit) : ($sumKredit - $sumDebit);

            $item = [
                'id' => $acc->id,
                'kode_akun' => $acc->kode_akun,
                'nama_akun' => $acc->nama_akun,
                'saldo' => $balance,
            ];

            if ($acc->tipe_akun === 'Aset') {
                $aset[] = $item;
                $totalAset += $balance;
            } elseif ($acc->tipe_akun === 'Kewajiban') {
                $kewajiban[] = $item;
                $totalKewajiban += $balance;
            } else {
                $asetNeto[] = $item;
                $totalAsetNeto += $balance;
            }
        }

        // Hitung Surplus/Defisit Berjalan (Penerimaan - Beban) sebagai penyeimbang Aset Neto
        $tipeSums = JurnalItem::join('chart_of_accounts', 'jurnal_items.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->join('jurnal_umums', 'jurnal_items.jurnal_umum_id', '=', 'jurnal_umums.id')
            ->whereDate('jurnal_umums.tanggal_transaksi', '<=', $asOfDate)
            ->whereIn('chart_of_accounts.tipe_akun', ['Penerimaan', 'Beban'])
            ->selectRaw('chart_of_accounts.tipe_akun, SUM(jurnal_items.debit) as total_debit, SUM(jurnal_items.kredit) as total_kredit')
            ->groupBy('chart_of_accounts.tipe_akun')
            ->get()
            ->keyBy('tipe_akun');

        $penerimaanRow = $tipeSums->get('Penerimaan');
        $totalPenerimaan = $penerimaanRow ? ((float) $penerimaanRow->total_kredit - (float) $penerimaanRow->total_debit) : 0.0;

        $bebanRow = $tipeSums->get('Beban');
        $totalBeban = $bebanRow ? ((float) $bebanRow->total_debit - (float) $bebanRow->total_kredit) : 0.0;

        $surplusBerjalan = $totalPenerimaan - $totalBeban;
        $totalAsetNeto += $surplusBerjalan;

        $asetNeto[] = [
            'id' => 'surplus-berjalan',
            'kode_akun' => '3-9999',
            'nama_akun' => 'Surplus / (Defisit) Periode Berjalan',
            'saldo' => $surplusBerjalan,
        ];

        return $this->successResponse([
            'as_of_date' => $asOfDate,
            'aset' => [
                'items' => $aset,
                'total' => $totalAset,
            ],
            'kewajiban' => [
                'items' => $kewajiban,
                'total' => $totalKewajiban,
            ],
            'aset_neto' => [
                'items' => $asetNeto,
                'total' => $totalAsetNeto,
            ],
            'total_kewajiban_dan_aset_neto' => $totalKewajiban + $totalAsetNeto,
            'is_balanced' => round($totalAset, 2) === round($totalKewajiban + $totalAsetNeto, 2),
        ], 'Laporan Posisi Keuangan (Neraca) berhasil dihitung');
    }

    /**
     * Laporan Aktivitas / Kinerja Keuangan Nirlaba (Penerimaan vs Beban)
     */
    public function laporanAktivitas(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfYear()->toDateString());

        // Single query aggregating all sums for Penerimaan & Beban within the date range
        $aggregatedSums = JurnalItem::selectRaw('chart_of_account_id, SUM(debit) as total_debit, SUM(kredit) as total_kredit')
            ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                $q->whereDate('tanggal_transaksi', '>=', $startDate)
                    ->whereDate('tanggal_transaksi', '<=', $endDate);
            })
            ->groupBy('chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        // 1. Penerimaan
        $penerimaanAccounts = ChartOfAccount::where('tipe_akun', 'Penerimaan')->orderBy('kode_akun')->get();
        $penerimaanList = [];
        $totalPenerimaan = 0;

        foreach ($penerimaanAccounts as $acc) {
            $sums = $aggregatedSums->get($acc->id);
            $sumDebit = $sums ? (float) $sums->total_debit : 0.0;
            $sumKredit = $sums ? (float) $sums->total_kredit : 0.0;

            $saldo = $sumKredit - $sumDebit;
            $penerimaanList[] = [
                'kode_akun' => $acc->kode_akun,
                'nama_akun' => $acc->nama_akun,
                'nominal' => $saldo,
            ];
            $totalPenerimaan += $saldo;
        }

        // 2. Beban Penyaluran & Operasional
        $bebanAccounts = ChartOfAccount::where('tipe_akun', 'Beban')->orderBy('kode_akun')->get();
        $bebanList = [];
        $totalBeban = 0;

        foreach ($bebanAccounts as $acc) {
            $sums = $aggregatedSums->get($acc->id);
            $sumDebit = $sums ? (float) $sums->total_debit : 0.0;
            $sumKredit = $sums ? (float) $sums->total_kredit : 0.0;

            $saldo = $sumDebit - $sumKredit;
            $bebanList[] = [
                'kode_akun' => $acc->kode_akun,
                'nama_akun' => $acc->nama_akun,
                'nominal' => $saldo,
            ];
            $totalBeban += $saldo;
        }

        $surplusDefisit = $totalPenerimaan - $totalBeban;

        return $this->successResponse([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'penerimaan' => [
                'items' => $penerimaanList,
                'total' => $totalPenerimaan,
            ],
            'beban' => [
                'items' => $bebanList,
                'total' => $totalBeban,
            ],
            'surplus_defisit' => $surplusDefisit,
        ], 'Laporan Aktivitas berhasil dihitung');
    }

    /**
     * Laporan Arus Kas (Metode Langsung - ISAK 35)
     */
    public function arusKas(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfYear()->toDateString());

        // Akun-akun kas & bank (kode awalan 1-1xxx)
        $cashAccounts = ChartOfAccount::where('tipe_akun', 'Aset')
            ->where(function ($q) {
                $q->where('kode_akun', 'like', '1-1%')
                    ->orWhere('nama_akun', 'like', '%Kas%')
                    ->orWhere('nama_akun', 'like', '%Bank%');
            })
            ->pluck('id');

        // Saldo Awal Kas & Bank sebelum $startDate
        $kasSumsBefore = JurnalItem::whereIn('chart_of_account_id', $cashAccounts)
            ->whereHas('jurnal', function ($q) use ($startDate) {
                $q->whereDate('tanggal_transaksi', '<', $startDate);
            })
            ->selectRaw('SUM(debit) as total_debit, SUM(kredit) as total_kredit')
            ->first();

        $saldoAwalKas = $kasSumsBefore ? ((float) $kasSumsBefore->total_debit - (float) $kasSumsBefore->total_kredit) : 0.0;

        // Ambil semua transaksi penerimaan dan pengeluaran kas dalam periode
        $tipeSumsInPeriod = JurnalItem::join('chart_of_accounts', 'jurnal_items.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->join('jurnal_umums', 'jurnal_items.jurnal_umum_id', '=', 'jurnal_umums.id')
            ->whereDate('jurnal_umums.tanggal_transaksi', '>=', $startDate)
            ->whereDate('jurnal_umums.tanggal_transaksi', '<=', $endDate)
            ->selectRaw('chart_of_accounts.tipe_akun, chart_of_accounts.kode_akun, chart_of_accounts.nama_akun, SUM(jurnal_items.debit) as total_debit, SUM(jurnal_items.kredit) as total_kredit')
            ->groupBy('chart_of_accounts.tipe_akun', 'chart_of_accounts.kode_akun', 'chart_of_accounts.nama_akun')
            ->get();

        // 1. Arus Kas dari Aktivitas Operasi
        $arusOperasiMasuk = [];
        $arusOperasiKeluar = [];
        $totalOperasiMasuk = 0;
        $totalOperasiKeluar = 0;

        // 2. Arus Kas dari Aktivitas Investasi (Aset Tetap / Peralatan kode 1-2xxx / Inventaris)
        $arusInvestasiMasuk = [];
        $arusInvestasiKeluar = [];
        $totalInvestasiMasuk = 0;
        $totalInvestasiKeluar = 0;

        // 3. Arus Kas dari Aktivitas Pendanaan (Dana Terikat Abadi / Wakaf Gedung kode 3-xxx / Pinjaman 2-xxx)
        $arusPendanaanMasuk = [];
        $arusPendanaanKeluar = [];
        $totalPendanaanMasuk = 0;
        $totalPendanaanKeluar = 0;

        foreach ($tipeSumsInPeriod as $row) {
            $debit = (float) $row->total_debit;
            $kredit = (float) $row->total_kredit;

            if ($row->tipe_akun === 'Penerimaan') {
                $nominal = $kredit - $debit;
                if ($nominal != 0) {
                    if (str_starts_with($row->kode_akun, '4-3') || stripos($row->nama_akun, 'Wakaf') !== false) {
                        $arusPendanaanMasuk[] = ['nama' => $row->nama_akun, 'nominal' => $nominal];
                        $totalPendanaanMasuk += $nominal;
                    } else {
                        $arusOperasiMasuk[] = ['nama' => $row->nama_akun, 'nominal' => $nominal];
                        $totalOperasiMasuk += $nominal;
                    }
                }
            } elseif ($row->tipe_akun === 'Beban') {
                $nominal = $debit - $kredit;
                if ($nominal != 0) {
                    $arusOperasiKeluar[] = ['nama' => $row->nama_akun, 'nominal' => -$nominal];
                    $totalOperasiKeluar += $nominal;
                }
            } elseif ($row->tipe_akun === 'Aset' && !str_starts_with($row->kode_akun, '1-1')) {
                // Mutasi Aset Tetap / Inventaris
                $netInvestasi = $debit - $kredit; // beli aset = arus kas keluar
                if ($netInvestasi > 0) {
                    $arusInvestasiKeluar[] = ['nama' => 'Pengeluaran untuk ' . $row->nama_akun, 'nominal' => -$netInvestasi];
                    $totalInvestasiKeluar += $netInvestasi;
                } elseif ($netInvestasi < 0) {
                    $arusInvestasiMasuk[] = ['nama' => 'Pelepasan/Penjualan ' . $row->nama_akun, 'nominal' => abs($netInvestasi)];
                    $totalInvestasiMasuk += abs($netInvestasi);
                }
            }
        }

        $netOperasi = $totalOperasiMasuk - $totalOperasiKeluar;
        $netInvestasi = $totalInvestasiMasuk - $totalInvestasiKeluar;
        $netPendanaan = $totalPendanaanMasuk - $totalPendanaanKeluar;

        $kenaikanPenurunanKas = $netOperasi + $netInvestasi + $netPendanaan;
        $saldoAkhirKas = $saldoAwalKas + $kenaikanPenurunanKas;

        return $this->successResponse([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'saldo_awal_kas' => $saldoAwalKas,
            'aktivitas_operasi' => [
                'masuk' => $arusOperasiMasuk,
                'keluar' => $arusOperasiKeluar,
                'total_masuk' => $totalOperasiMasuk,
                'total_keluar' => $totalOperasiKeluar,
                'net' => $netOperasi,
            ],
            'aktivitas_investasi' => [
                'masuk' => $arusInvestasiMasuk,
                'keluar' => $arusInvestasiKeluar,
                'net' => $netInvestasi,
            ],
            'aktivitas_pendanaan' => [
                'masuk' => $arusPendanaanMasuk,
                'keluar' => $arusPendanaanKeluar,
                'net' => $netPendanaan,
            ],
            'kenaikan_penurunan_kas' => $kenaikanPenurunanKas,
            'saldo_akhir_kas' => $saldoAkhirKas,
        ], 'Laporan Arus Kas ISAK 35 berhasil dihitung');
    }
}
