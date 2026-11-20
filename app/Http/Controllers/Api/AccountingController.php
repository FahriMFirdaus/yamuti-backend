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

        $runningBalance = 0;
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

        $aset = [];
        $kewajiban = [];
        $asetNeto = [];

        $totalAset = 0;
        $totalKewajiban = 0;
        $totalAsetNeto = 0;

        foreach ($accounts as $acc) {
            $sumDebit = JurnalItem::where('chart_of_account_id', $acc->id)
                ->whereHas('jurnal', function ($q) use ($asOfDate) {
                    $q->whereDate('tanggal_transaksi', '<=', $asOfDate);
                })->sum('debit');

            $sumKredit = JurnalItem::where('chart_of_account_id', $acc->id)
                ->whereHas('jurnal', function ($q) use ($asOfDate) {
                    $q->whereDate('tanggal_transaksi', '<=', $asOfDate);
                })->sum('kredit');

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
        $totalPenerimaan = JurnalItem::whereHas('account', fn ($q) => $q->where('tipe_akun', 'Penerimaan'))
            ->whereHas('jurnal', fn ($q) => $q->whereDate('tanggal_transaksi', '<=', $asOfDate))
            ->sum('kredit') - JurnalItem::whereHas('account', fn ($q) => $q->where('tipe_akun', 'Penerimaan'))
            ->whereHas('jurnal', fn ($q) => $q->whereDate('tanggal_transaksi', '<=', $asOfDate))
            ->sum('debit');

        $totalBeban = JurnalItem::whereHas('account', fn ($q) => $q->where('tipe_akun', 'Beban'))
            ->whereHas('jurnal', fn ($q) => $q->whereDate('tanggal_transaksi', '<=', $asOfDate))
            ->sum('debit') - JurnalItem::whereHas('account', fn ($q) => $q->where('tipe_akun', 'Beban'))
            ->whereHas('jurnal', fn ($q) => $q->whereDate('tanggal_transaksi', '<=', $asOfDate))
            ->sum('kredit');

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

        // 1. Penerimaan
        $penerimaanAccounts = ChartOfAccount::where('tipe_akun', 'Penerimaan')->orderBy('kode_akun')->get();
        $penerimaanList = [];
        $totalPenerimaan = 0;

        foreach ($penerimaanAccounts as $acc) {
            $sumKredit = JurnalItem::where('chart_of_account_id', $acc->id)
                ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                    $q->whereDate('tanggal_transaksi', '>=', $startDate)
                        ->whereDate('tanggal_transaksi', '<=', $endDate);
                })->sum('kredit');

            $sumDebit = JurnalItem::where('chart_of_account_id', $acc->id)
                ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                    $q->whereDate('tanggal_transaksi', '>=', $startDate)
                        ->whereDate('tanggal_transaksi', '<=', $endDate);
                })->sum('debit');

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
            $sumDebit = JurnalItem::where('chart_of_account_id', $acc->id)
                ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                    $q->whereDate('tanggal_transaksi', '>=', $startDate)
                        ->whereDate('tanggal_transaksi', '<=', $endDate);
                })->sum('debit');

            $sumKredit = JurnalItem::where('chart_of_account_id', $acc->id)
                ->whereHas('jurnal', function ($q) use ($startDate, $endDate) {
                    $q->whereDate('tanggal_transaksi', '>=', $startDate)
                        ->whereDate('tanggal_transaksi', '<=', $endDate);
                })->sum('kredit');

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
}
