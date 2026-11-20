<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransaksiKeuanganRequest;
use App\Models\TransaksiKeuangan;
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
        $data = $this->transaksiService->getAllTransaksi((int) $request->query('per_page', 15));

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
        $tanggal = $request->query('tanggal'); // format YYYY-MM-DD
        $bulan = $request->query('bulan', date('m'));
        $tahun = $request->query('tahun', date('Y'));
        $jenis = $request->query('jenis'); // pemasukan/pengeluaran -> Debit/Kredit
        $formatStandar = $request->query('standar'); // 'isak35'

        $query = TransaksiKeuangan::query();

        // Jika parameter tanggal spesifik dikirim, utamakan pencarian per hari
        if ($tanggal) {
            $query->whereDate('created_at', $tanggal);
        } else {
            // Jika tidak, gunakan pencarian bulanan (default)
            $query->whereYear('created_at', $tahun)
                ->whereMonth('created_at', $bulan);
        }

        if ($jenis === 'pemasukan') {
            $query->where('tipe_transaksi', 'Debit');
        } elseif ($jenis === 'pengeluaran') {
            $query->where('tipe_transaksi', 'Kredit');
        }

        $transaksi = $query->latest()->get();

        // Jika format ISAK 35 diminta, kelompokkan aset neto terikat vs tidak terikat
        if ($formatStandar === 'isak35') {
            $danaTerikat = [];
            $danaTidakTerikat = [];

            $totalMasukTerikat = 0;
            $totalKeluarTerikat = 0;
            $totalMasukTidakTerikat = 0;
            $totalKeluarTidakTerikat = 0;

            foreach ($transaksi as $item) {
                // Pos Terikat: Memiliki donasi_id (program spesifik) atau kas kampanye/bantuan
                $isTerikat = ! empty($item->donasi_id) ||
                             str_contains(strtolower($item->deskripsi), 'kampanye') ||
                             str_contains(strtolower($item->deskripsi), 'program') ||
                             str_contains(strtolower($item->jenis_kas), 'program');

                $nom = (float) $item->nominal;

                if ($isTerikat) {
                    $danaTerikat[] = $item;
                    if ($item->tipe_transaksi === 'Debit') {
                        $totalMasukTerikat += $nom;
                    } else {
                        $totalKeluarTerikat += $nom;
                    }
                } else {
                    $danaTidakTerikat[] = $item;
                    if ($item->tipe_transaksi === 'Debit') {
                        $totalMasukTidakTerikat += $nom;
                    } else {
                        $totalKeluarTidakTerikat += $nom;
                    }
                }
            }

            return $this->successResponse([
                'standar' => 'ISAK 35 / PSAK 409 Entitas Nirlaba',
                'periode' => ['bulan' => $bulan, 'tahun' => $tahun, 'tanggal' => $tanggal],
                'ringkasan' => [
                    'dana_tidak_terikat' => [
                        'pemasukan' => $totalMasukTidakTerikat,
                        'pengeluaran' => $totalKeluarTidakTerikat,
                        'kenaikan_bersih' => $totalMasukTidakTerikat - $totalKeluarTidakTerikat,
                    ],
                    'dana_terikat' => [
                        'pemasukan' => $totalMasukTerikat,
                        'pengeluaran' => $totalKeluarTerikat,
                        'kenaikan_bersih' => $totalMasukTerikat - $totalKeluarTerikat,
                    ],
                    'total_saldo_periode' => ($totalMasukTidakTerikat + $totalMasukTerikat) - ($totalKeluarTidakTerikat + $totalKeluarTerikat),
                ],
                'rincian' => [
                    'dana_tidak_terikat' => $danaTidakTerikat,
                    'dana_terikat' => $danaTerikat,
                ],
            ], 'Laporan keuangan standar ISAK 35 berhasil digenerate');
        }

        return $this->successResponse($transaksi, 'Laporan keuangan berhasil diambil');
    }

    public function show($id): JsonResponse
    {
        $transaksi = TransaksiKeuangan::with('creator')->findOrFail($id);
        return $this->successResponse($transaksi, 'Detail transaksi keuangan berhasil diambil');
    }

    public function destroy($id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $transaksi = TransaksiKeuangan::findOrFail($id);

            // Hapus Jurnal Umum terkait jika ada
            $jurnal = \App\Models\JurnalUmum::where('referensi_id', $transaksi->id)
                ->where('tipe_referensi', 'transaksi_keuangan')
                ->first();

            if ($jurnal) {
                $jurnal->items()->delete();
                $jurnal->delete();
            }

            $transaksi->delete();

            return $this->successResponse(null, 'Transaksi keuangan berhasil dihapus dan jurnal disinkronkan');
        });
    }

    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'amountRaw' => 'required|numeric|min:1',
            'status' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $transaksi = TransaksiKeuangan::findOrFail($id);

            // Hapus tanda "DRAFT: " dari deskripsi jika sebelumnya berupa draft
            $deskripsi = str_replace('DRAFT: ', '', $transaksi->deskripsi);
            $newNominal = (float) $validated['amountRaw'];

            $transaksi->update([
                'nominal' => $newNominal,
                'deskripsi' => $deskripsi,
            ]);

            // Sinkronkan ke Jurnal Umum terkait
            $jurnal = \App\Models\JurnalUmum::where('referensi_id', $transaksi->id)
                ->where('tipe_referensi', 'transaksi_keuangan')
                ->first();

            if ($jurnal) {
                $jurnal->update([
                    'total_debit' => $newNominal,
                    'total_kredit' => $newNominal,
                    'keterangan' => $deskripsi,
                ]);

                foreach ($jurnal->items as $item) {
                    if ($item->debit > 0) {
                        $item->update(['debit' => $newNominal]);
                    }
                    if ($item->kredit > 0) {
                        $item->update(['kredit' => $newNominal]);
                    }
                }
            }

            return $this->successResponse($transaksi, 'Transaksi keuangan dan jurnal akuntansi berhasil diperbarui dan disahkan');
        });
    }
}
