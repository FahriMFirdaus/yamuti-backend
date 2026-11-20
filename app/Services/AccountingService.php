<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Donasi;
use App\Models\JurnalItem;
use App\Models\JurnalUmum;
use App\Models\TransaksiKeuangan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountingService
{
    /**
     * Otomatis posting jurnal saat donasi lunas/terverifikasi
     */
    public function recordDonasiJournal(Donasi $donasi): ?JurnalUmum
    {
        // Hindari duplikasi jurnal jika sudah pernah dicatat
        $existing = JurnalUmum::where('sumber_transaksi', 'Donasi')
            ->where('referensi_id', $donasi->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($donasi) {
            $nomorRef = 'JUR-DON-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            $keterangan = 'Penerimaan Donasi: ' . ($donasi->kampanye->judul ?? ($donasi->orang_tua_asuh_id ? 'Beasiswa POTA' : 'Infaq Umum')) . ' a.n ' . ($donasi->nama_donatur ?: 'Hamba Allah');

            $jurnal = JurnalUmum::create([
                'id' => (string) Str::uuid(),
                'nomor_referensi' => $nomorRef,
                'tanggal_transaksi' => $donasi->created_at ? Carbon::parse($donasi->created_at)->toDateString() : now()->toDateString(),
                'keterangan' => $keterangan,
                'sumber_transaksi' => 'Donasi',
                'referensi_id' => $donasi->id,
                'created_by' => $donasi->user_id,
            ]);

            // Tentukan Akun Kas/Bank (Debit)
            $kasAccount = ChartOfAccount::where('kode_akun', '1-1003')->first(); // Bank BSI
            if (! $kasAccount) {
                $kasAccount = ChartOfAccount::where('tipe_akun', 'Aset')->first();
            }

            // Tentukan Akun Pendapatan (Kredit)
            $kodeKredit = '4-1001'; // Default Infaq Umum
            if ($donasi->orang_tua_asuh_id) {
                $kodeKredit = '4-1003'; // Beasiswa POTA
            } elseif ($donasi->kampanye_id) {
                $judul = strtolower($donasi->kampanye->judul ?? '');
                if (str_contains($judul, 'zakat')) {
                    $kodeKredit = '4-1004'; // Zakat
                } else {
                    $kodeKredit = '4-1002'; // Kampanye Khusus
                }
            }

            $kreditAccount = ChartOfAccount::where('kode_akun', $kodeKredit)->first();
            if (! $kreditAccount) {
                $kreditAccount = ChartOfAccount::where('tipe_akun', 'Penerimaan')->first();
            }

            $nominal = (float) $donasi->gross_amount;

            // Debit Entry (Kas Masuk)
            JurnalItem::create([
                'id' => (string) Str::uuid(),
                'jurnal_umum_id' => $jurnal->id,
                'chart_of_account_id' => $kasAccount->id,
                'debit' => $nominal,
                'kredit' => 0,
                'memo' => 'Penerimaan dana donasi ' . $donasi->order_id,
            ]);

            // Kredit Entry (Pendapatan Diakui)
            JurnalItem::create([
                'id' => (string) Str::uuid(),
                'jurnal_umum_id' => $jurnal->id,
                'chart_of_account_id' => $kreditAccount->id,
                'debit' => 0,
                'kredit' => $nominal,
                'memo' => 'Penerimaan donasi ' . $keterangan,
            ]);

            return $jurnal;
        });
    }

    /**
     * Otomatis posting jurnal saat admin mencatat transaksi kas keluar/masuk
     */
    public function recordKasJournal(TransaksiKeuangan $kas): ?JurnalUmum
    {
        $existing = JurnalUmum::where('sumber_transaksi', 'Kas Manual')
            ->where('referensi_id', $kas->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($kas) {
            $nomorRef = 'JUR-KAS-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            $isPengeluaran = strtolower($kas->tipe_transaksi) === 'kredit';

            $jurnal = JurnalUmum::create([
                'id' => (string) Str::uuid(),
                'nomor_referensi' => $nomorRef,
                'tanggal_transaksi' => $kas->created_at ? Carbon::parse($kas->created_at)->toDateString() : now()->toDateString(),
                'keterangan' => ($isPengeluaran ? 'Pengeluaran: ' : 'Pemasukan Kas: ') . $kas->deskripsi,
                'sumber_transaksi' => 'Kas Manual',
                'referensi_id' => $kas->id,
                'created_by' => $kas->created_by,
            ]);

            $nominal = (float) $kas->nominal;

            // Mapping Akun Kas berdasarkan jenis_kas
            $kasAccount = null;
            if (str_contains(strtolower($kas->jenis_kas), 'website') || str_contains(strtolower($kas->jenis_kas), 'it')) {
                $kasAccount = ChartOfAccount::where('kode_akun', '1-1002')->first();
            } elseif (str_contains(strtolower($kas->jenis_kas), 'cabang') || str_contains(strtolower($kas->jenis_kas), 'asrama')) {
                $kasAccount = ChartOfAccount::where('kode_akun', '1-1004')->first();
            } else {
                $kasAccount = ChartOfAccount::where('kode_akun', '1-1001')->first(); // Kas Tunai Yayasan
            }

            if (! $kasAccount) {
                $kasAccount = ChartOfAccount::where('tipe_akun', 'Aset')->first();
            }

            if ($isPengeluaran) {
                // Pengeluaran / Beban:
                // Debit: Beban terkait
                // Kredit: Kas berkurang
                $bebanAccount = null;
                $kategori = strtolower($kas->kategori_pengeluaran ?? '');

                if (str_contains($kategori, 'pendidikan') || str_contains($kategori, 'beasiswa') || str_contains($kategori, 'santri')) {
                    $bebanAccount = ChartOfAccount::where('kode_akun', '5-1001')->first();
                } elseif (str_contains($kategori, 'logistik') || str_contains($kategori, 'pangan') || str_contains($kategori, 'konsumsi')) {
                    $bebanAccount = ChartOfAccount::where('kode_akun', '5-1002')->first();
                } elseif (str_contains($kategori, 'listrik') || str_contains($kategori, 'air') || str_contains($kategori, 'operasional')) {
                    $bebanAccount = ChartOfAccount::where('kode_akun', '5-1003')->first();
                } elseif (str_contains($kategori, 'pemeliharaan') || str_contains($kategori, 'renovasi')) {
                    $bebanAccount = ChartOfAccount::where('kode_akun', '5-1004')->first();
                } elseif (str_contains($kategori, 'it') || str_contains($kategori, 'server') || str_contains($kategori, 'website')) {
                    $bebanAccount = ChartOfAccount::where('kode_akun', '5-1005')->first();
                } else {
                    $bebanAccount = ChartOfAccount::where('kode_akun', '5-1006')->first(); // Administrasi Umum
                }

                if (! $bebanAccount) {
                    $bebanAccount = ChartOfAccount::where('tipe_akun', 'Beban')->first();
                }

                // Debit Beban
                JurnalItem::create([
                    'id' => (string) Str::uuid(),
                    'jurnal_umum_id' => $jurnal->id,
                    'chart_of_account_id' => $bebanAccount->id,
                    'debit' => $nominal,
                    'kredit' => 0,
                    'memo' => $kas->deskripsi,
                ]);

                // Kredit Kas
                JurnalItem::create([
                    'id' => (string) Str::uuid(),
                    'jurnal_umum_id' => $jurnal->id,
                    'chart_of_account_id' => $kasAccount->id,
                    'debit' => 0,
                    'kredit' => $nominal,
                    'memo' => 'Pengeluaran dari ' . $kas->jenis_kas,
                ]);
            } else {
                // Pemasukan Manual Kas:
                // Debit: Kas bertambah
                // Kredit: Pendapatan Infaq
                $pendapatanAccount = ChartOfAccount::where('kode_akun', '4-1001')->first();
                if (! $pendapatanAccount) {
                    $pendapatanAccount = ChartOfAccount::where('tipe_akun', 'Penerimaan')->first();
                }

                JurnalItem::create([
                    'id' => (string) Str::uuid(),
                    'jurnal_umum_id' => $jurnal->id,
                    'chart_of_account_id' => $kasAccount->id,
                    'debit' => $nominal,
                    'kredit' => 0,
                    'memo' => 'Pemasukan ke ' . $kas->jenis_kas,
                ]);

                JurnalItem::create([
                    'id' => (string) Str::uuid(),
                    'jurnal_umum_id' => $jurnal->id,
                    'chart_of_account_id' => $pendapatanAccount->id,
                    'debit' => 0,
                    'kredit' => $nominal,
                    'memo' => $kas->deskripsi,
                ]);
            }

            return $jurnal;
        });
    }
}
