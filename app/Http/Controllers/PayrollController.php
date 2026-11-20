<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Karyawan;
use App\Models\Payroll;
use App\Services\AccountingService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayrollController extends Controller
{
    use ApiResponse;

    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Display a listing of payrolls with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Payroll::with(['karyawan', 'kasBankAkun', 'jurnalUmum']);

        // Filter by Periode Bulan & Tahun
        if ($bulan = $request->input('periode_bulan', $request->input('bulan'))) {
            $query->where('periode_bulan', (int) $bulan);
        }
        if ($tahun = $request->input('periode_tahun', $request->input('tahun'))) {
            $query->where('periode_tahun', (int) $tahun);
        }

        // Filter by Status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by Tipe SDM
        if ($tipe = $request->input('tipe_sdm')) {
            $query->whereHas('karyawan', function ($q) use ($tipe) {
                $q->where('tipe_sdm', $tipe);
            });
        }

        // Search by employee name or slip number
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_slip', 'like', "%{$search}%")
                    ->orWhereHas('karyawan', function ($sub) use ($search) {
                        $sub->where('nama_lengkap', 'like', "%{$search}%")
                            ->orWhere('nip', 'like', "%{$search}%");
                    });
            });
        }

        // Ordering
        $query->orderBy('periode_tahun', 'desc')
            ->orderBy('periode_bulan', 'desc')
            ->orderBy('created_at', 'desc');

        $perPage = $request->input('per_page', 15);
        if ($perPage === 'all') {
            $data = $query->get();
        } else {
            $data = $query->paginate((int) $perPage);
        }

        return $this->successResponse($data, 'Data payroll berhasil diambil');
    }

    /**
     * Summary statistics for payroll.
     */
    public function summary(Request $request): JsonResponse
    {
        $bulan = (int) ($request->input('periode_bulan', $request->input('bulan', date('n'))));
        $tahun = (int) ($request->input('periode_tahun', $request->input('tahun', date('Y'))));

        $baseQuery = Payroll::where('periode_bulan', $bulan)->where('periode_tahun', $tahun);

        $summary = [
            'periode_bulan' => $bulan,
            'periode_tahun' => $tahun,
            'total_slip' => (clone $baseQuery)->count(),
            'total_draft' => (clone $baseQuery)->where('status', 'Draft')->count(),
            'total_disetujui' => (clone $baseQuery)->where('status', 'Disetujui')->count(),
            'total_dibayar' => (clone $baseQuery)->where('status', 'Dibayar')->count(),
            'nominal_dibayar' => (float) (clone $baseQuery)->where('status', 'Dibayar')->sum('gaji_bersih'),
            'nominal_draft' => (float) (clone $baseQuery)->where('status', 'Draft')->sum('gaji_bersih'),
            'nominal_disetujui' => (float) (clone $baseQuery)->where('status', 'Disetujui')->sum('gaji_bersih'),
            'total_pengeluaran_est' => (float) (clone $baseQuery)->sum('gaji_bersih'),
        ];

        return $this->successResponse($summary, 'Summary payroll berhasil diambil');
    }

    /**
     * Store a single payroll record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|uuid|exists:karyawans,id',
            'periode_bulan' => 'required|integer|between:1,12',
            'periode_tahun' => 'required|integer|min:2020|max:2099',
            'gaji_pokok' => 'nullable|numeric|min:0',
            'tunjangan_jabatan' => 'nullable|numeric|min:0',
            'tunjangan_kehadiran' => 'nullable|numeric|min:0',
            'tunjangan_keluarga' => 'nullable|numeric|min:0',
            'bonus_tambahan' => 'nullable|numeric|min:0',
            'potongan_bpjs' => 'nullable|numeric|min:0',
            'potongan_kasbon' => 'nullable|numeric|min:0',
            'potongan_terlambat' => 'nullable|numeric|min:0',
            'potongan_lain' => 'nullable|numeric|min:0',
            'metode_pembayaran' => 'nullable|in:Transfer Bank,Kas Tunai',
            'kas_bank_akun_id' => 'nullable|uuid|exists:chart_of_accounts,id',
            'catatan' => 'nullable|string',
        ]);

        // Check if payroll already exists for this karyawan and period
        $exists = Payroll::where('karyawan_id', $validated['karyawan_id'])
            ->where('periode_bulan', $validated['periode_bulan'])
            ->where('periode_tahun', $validated['periode_tahun'])
            ->exists();

        if ($exists) {
            return $this->errorResponse('Slip gaji untuk karyawan ini pada periode yang dipilih sudah ada.', 422);
        }

        // Calculate earnings & deductions
        $gajiPokok = (float) ($validated['gaji_pokok'] ?? 0);
        $tunJabatan = (float) ($validated['tunjangan_jabatan'] ?? 0);
        $tunKehadiran = (float) ($validated['tunjangan_kehadiran'] ?? 0);
        $tunKeluarga = (float) ($validated['tunjangan_keluarga'] ?? 0);
        $bonus = (float) ($validated['bonus_tambahan'] ?? 0);
        $totalPendapatan = $gajiPokok + $tunJabatan + $tunKehadiran + $tunKeluarga + $bonus;

        $potBpjs = (float) ($validated['potongan_bpjs'] ?? 0);
        $potKasbon = (float) ($validated['potongan_kasbon'] ?? 0);
        $potTerlambat = (float) ($validated['potongan_terlambat'] ?? 0);
        $potLain = (float) ($validated['potongan_lain'] ?? 0);
        $totalPotongan = $potBpjs + $potKasbon + $potTerlambat + $potLain;

        $gajiBersih = max(0, $totalPendapatan - $totalPotongan);

        // Auto-generate nomor slip: SLIP-YYYYMM-XXXX
        $nomorSlip = $this->generateNomorSlip($validated['periode_tahun'], $validated['periode_bulan']);

        $payroll = Payroll::create(array_merge($validated, [
            'nomor_slip' => $nomorSlip,
            'gaji_pokok' => $gajiPokok,
            'tunjangan_jabatan' => $tunJabatan,
            'tunjangan_kehadiran' => $tunKehadiran,
            'tunjangan_keluarga' => $tunKeluarga,
            'bonus_tambahan' => $bonus,
            'total_pendapatan' => $totalPendapatan,
            'potongan_bpjs' => $potBpjs,
            'potongan_kasbon' => $potKasbon,
            'potongan_terlambat' => $potTerlambat,
            'potongan_lain' => $potLain,
            'total_potongan' => $totalPotongan,
            'gaji_bersih' => $gajiBersih,
            'status' => 'Draft',
            'created_by' => $request->user()?->id,
        ]));

        $payroll->load(['karyawan', 'kasBankAkun']);

        return $this->successResponse($payroll, 'Draft payroll berhasil dibuat', 201);
    }

    /**
     * Bulk generate draft payrolls for active employees for a given period.
     */
    public function generateBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periode_bulan' => 'required|integer|between:1,12',
            'periode_tahun' => 'required|integer|min:2020|max:2099',
            'tipe_sdm' => 'nullable|in:Pengasuh/Ustadz,Staf/Amil',
        ]);

        $bulan = (int) $validated['periode_bulan'];
        $tahun = (int) $validated['periode_tahun'];

        // Fetch active employees who don't have a payroll record for this period yet
        $employeesQuery = Karyawan::where('status_aktif', 'Aktif')
            ->whereDoesntHave('payrolls', function ($q) use ($bulan, $tahun) {
                $q->where('periode_bulan', $bulan)->where('periode_tahun', $tahun);
            });

        if (! empty($validated['tipe_sdm'])) {
            $employeesQuery->where('tipe_sdm', $validated['tipe_sdm']);
        }

        $employees = $employeesQuery->get();

        if ($employees->isEmpty()) {
            return $this->successResponse([
                'generated_count' => 0,
                'items' => [],
            ], 'Tidak ada karyawan aktif yang perlu dibuatkan payroll untuk periode ini');
        }

        $created = [];
        DB::transaction(function () use ($employees, $bulan, $tahun, $request, &$created) {
            foreach ($employees as $emp) {
                $nomorSlip = $this->generateNomorSlip($tahun, $bulan);
                $gajiPokok = (float) $emp->gaji_pokok_standar;
                $tunJabatan = (float) $emp->tunjangan_tetap_standar;
                $totalPendapatan = $gajiPokok + $tunJabatan;
                $gajiBersih = $totalPendapatan;

                $payroll = Payroll::create([
                    'nomor_slip' => $nomorSlip,
                    'karyawan_id' => $emp->id,
                    'periode_bulan' => $bulan,
                    'periode_tahun' => $tahun,
                    'gaji_pokok' => $gajiPokok,
                    'tunjangan_jabatan' => $tunJabatan,
                    'tunjangan_kehadiran' => 0,
                    'tunjangan_keluarga' => 0,
                    'bonus_tambahan' => 0,
                    'total_pendapatan' => $totalPendapatan,
                    'potongan_bpjs' => 0,
                    'potongan_kasbon' => 0,
                    'potongan_terlambat' => 0,
                    'potongan_lain' => 0,
                    'total_potongan' => 0,
                    'gaji_bersih' => $gajiBersih,
                    'status' => 'Draft',
                    'metode_pembayaran' => 'Transfer Bank',
                    'created_by' => $request->user()?->id,
                ]);

                $created[] = $payroll;
            }
        });

        return $this->successResponse([
            'generated_count' => count($created),
            'items' => $created,
        ], count($created) . ' draft payroll berhasil dibuat secara massal', 201);
    }

    /**
     * Display the specified payroll record.
     */
    public function show($id): JsonResponse
    {
        $targetId = $id instanceof Payroll ? $id->id : $id;

        $payroll = Payroll::with([
            'karyawan',
            'kasBankAkun',
            'jurnalUmum.items.account',
            'approver',
            'payer',
            'creator',
        ])->findOrFail($targetId);

        return $this->successResponse($payroll, 'Detail payroll berhasil diambil');
    }

    /**
     * Update the specified payroll (only if not Dibayar).
     */
    public function update(Request $request, $id): JsonResponse
    {
        $targetId = $id instanceof Payroll ? $id->id : $id;
        $payroll = Payroll::findOrFail($targetId);

        // Guard against updating paid payroll
        if ($payroll->status === 'Dibayar') {
            return $this->errorResponse(
                'Payroll yang sudah berstatus Dibayar tidak dapat diubah karena jurnal akuntansi ISAK 35 telah dicatat.',
                422
            );
        }

        $validated = $request->validate([
            'gaji_pokok' => 'sometimes|numeric|min:0',
            'tunjangan_jabatan' => 'sometimes|numeric|min:0',
            'tunjangan_kehadiran' => 'sometimes|numeric|min:0',
            'tunjangan_keluarga' => 'sometimes|numeric|min:0',
            'bonus_tambahan' => 'sometimes|numeric|min:0',
            'potongan_bpjs' => 'sometimes|numeric|min:0',
            'potongan_kasbon' => 'sometimes|numeric|min:0',
            'potongan_terlambat' => 'sometimes|numeric|min:0',
            'potongan_lain' => 'sometimes|numeric|min:0',
            'metode_pembayaran' => 'nullable|in:Transfer Bank,Kas Tunai',
            'kas_bank_akun_id' => 'nullable|uuid|exists:chart_of_accounts,id',
            'catatan' => 'nullable|string',
        ]);

        $gajiPokok = (float) ($validated['gaji_pokok'] ?? $payroll->gaji_pokok);
        $tunJabatan = (float) ($validated['tunjangan_jabatan'] ?? $payroll->tunjangan_jabatan);
        $tunKehadiran = (float) ($validated['tunjangan_kehadiran'] ?? $payroll->tunjangan_kehadiran);
        $tunKeluarga = (float) ($validated['tunjangan_keluarga'] ?? $payroll->tunjangan_keluarga);
        $bonus = (float) ($validated['bonus_tambahan'] ?? $payroll->bonus_tambahan);
        $totalPendapatan = $gajiPokok + $tunJabatan + $tunKehadiran + $tunKeluarga + $bonus;

        $potBpjs = (float) ($validated['potongan_bpjs'] ?? $payroll->potongan_bpjs);
        $potKasbon = (float) ($validated['potongan_kasbon'] ?? $payroll->potongan_kasbon);
        $potTerlambat = (float) ($validated['potongan_terlambat'] ?? $payroll->potongan_terlambat);
        $potLain = (float) ($validated['potongan_lain'] ?? $payroll->potongan_lain);
        $totalPotongan = $potBpjs + $potKasbon + $potTerlambat + $potLain;

        $gajiBersih = max(0, $totalPendapatan - $totalPotongan);

        $payroll->update(array_merge($validated, [
            'gaji_pokok' => $gajiPokok,
            'tunjangan_jabatan' => $tunJabatan,
            'tunjangan_kehadiran' => $tunKehadiran,
            'tunjangan_keluarga' => $tunKeluarga,
            'bonus_tambahan' => $bonus,
            'total_pendapatan' => $totalPendapatan,
            'potongan_bpjs' => $potBpjs,
            'potongan_kasbon' => $potKasbon,
            'potongan_terlambat' => $potTerlambat,
            'potongan_lain' => $potLain,
            'total_potongan' => $totalPotongan,
            'gaji_bersih' => $gajiBersih,
            'updated_by' => $request->user()?->id,
        ]));

        $payroll->load(['karyawan', 'kasBankAkun']);

        return $this->successResponse($payroll, 'Payroll berhasil diperbarui');
    }

    /**
     * Delete the specified payroll (only if not Dibayar).
     */
    public function destroy($id): JsonResponse
    {
        $targetId = $id instanceof Payroll ? $id->id : $id;
        $payroll = Payroll::findOrFail($targetId);

        if ($payroll->status === 'Dibayar') {
            return $this->errorResponse(
                'Payroll yang sudah berstatus Dibayar tidak dapat dihapus demi integritas pembukuan jurnal akuntansi.',
                422
            );
        }

        $payroll->delete();

        return $this->successResponse(null, 'Draft payroll berhasil dihapus');
    }

    /**
     * Approve payroll (Draft -> Disetujui).
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $targetId = $id instanceof Payroll ? $id->id : $id;
        $payroll = Payroll::findOrFail($targetId);

        if ($payroll->status === 'Dibayar') {
            return $this->errorResponse('Payroll sudah berstatus Dibayar.', 422);
        }

        $payroll->update([
            'status' => 'Disetujui',
            'disetujui_oleh' => $request->user()?->id,
            'disetujui_pada' => now(),
            'updated_by' => $request->user()?->id,
        ]);

        $payroll->load(['karyawan', 'kasBankAkun', 'approver']);

        return $this->successResponse($payroll, 'Payroll berhasil disetujui');
    }

    /**
     * Pay payroll (Disetujui / Draft -> Dibayar + ISAK 35 Journal Posting).
     */
    public function pay(Request $request, $id): JsonResponse
    {
        $targetId = $id instanceof Payroll ? $id->id : $id;

        $validated = $request->validate([
            'metode_pembayaran' => 'nullable|in:Transfer Bank,Kas Tunai',
            'kas_bank_akun_id' => 'nullable|uuid|exists:chart_of_accounts,id',
            'tanggal_pembayaran' => 'nullable|date',
            'catatan' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($targetId, $validated, $request) {
            $payroll = Payroll::lockForUpdate()->findOrFail($targetId);

            // Re-pay attempt rejected with HTTP 422
            if ($payroll->status === 'Dibayar') {
                $tgl = $payroll->tanggal_pembayaran ? Carbon::parse($payroll->tanggal_pembayaran)->format('d/m/Y') : 'sebelumnya';
                return $this->errorResponse(
                    "Payroll periode ini sudah berstatus Dibayar pada {$tgl}. Pembayaran ulang ditolak untuk mencegah duplikasi jurnal akuntansi.",
                    422
                );
            }

            $metode = $validated['metode_pembayaran'] ?? $payroll->metode_pembayaran ?? 'Transfer Bank';
            $tglBayar = $validated['tanggal_pembayaran'] ?? now()->toDateString();

            // Determine kas/bank account
            $kasAkunId = $validated['kas_bank_akun_id'] ?? $payroll->kas_bank_akun_id;
            $kodeKas = '1-1003'; // default BSI
            if (! empty($kasAkunId)) {
                $acc = ChartOfAccount::find($kasAkunId);
                if ($acc) {
                    $kodeKas = $acc->kode_akun;
                }
            } else {
                if (str_contains(strtolower($metode), 'tunai')) {
                    $kodeKas = '1-1001';
                }
                $acc = ChartOfAccount::where('kode_akun', $kodeKas)->first();
                if ($acc) {
                    $kasAkunId = $acc->id;
                }
            }

            // Update Payroll state
            $payroll->update([
                'status' => 'Dibayar',
                'metode_pembayaran' => $metode,
                'kas_bank_akun_id' => $kasAkunId,
                'tanggal_pembayaran' => $tglBayar,
                'dibayar_oleh' => $request->user()?->id,
                'dibayar_pada' => now(),
                'catatan' => $validated['catatan'] ?? $payroll->catatan,
                'updated_by' => $request->user()?->id,
            ]);

            // Auto-post ISAK 35 Journal via AccountingService
            $jurnal = $this->accountingService->recordPayrollJournal(
                $payroll,
                $kodeKas,
                $tglBayar,
                $request->user()?->id
            );

            $payroll->load([
                'karyawan',
                'kasBankAkun',
                'jurnalUmum.items.account',
                'approver',
                'payer',
            ]);

            return $this->successResponse($payroll, 'Payroll berhasil dibayarkan dan jurnal akuntansi ISAK 35 telah dicatat');
        });
    }

    /**
     * Slip Gaji printable structured payload with Indonesian Terbilang.
     */
    public function slip($id): JsonResponse
    {
        $targetId = $id instanceof Payroll ? $id->id : $id;

        $payroll = Payroll::with([
            'karyawan',
            'kasBankAkun',
            'jurnalUmum.items.account',
            'approver',
            'payer',
        ])->findOrFail($targetId);

        $karyawan = $payroll->karyawan;

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $terbilang = self::terbilangRupiah((float) $payroll->gaji_bersih);

        $payload = [
            'yayasan' => [
                'nama' => 'YAYASAN AMANAH MUTIARA UMMAT INDONESIA (YAMUTI)',
                'sk_kemenkumham' => 'AHU-0012345.AH.01.04.Tahun 2020',
                'alamat' => 'Tasikmalaya, Jawa Barat, Indonesia',
                'telepon' => '(0265) 123456',
                'email' => 'info@yamuti.or.id',
                'website' => 'https://yamuti.or.id',
            ],
            'slip' => [
                'id' => $payroll->id,
                'nomor_slip' => $payroll->nomor_slip,
                'periode_bulan' => $payroll->periode_bulan,
                'periode_tahun' => $payroll->periode_tahun,
                'nama_periode' => ($namaBulan[$payroll->periode_bulan] ?? $payroll->periode_bulan) . ' ' . $payroll->periode_tahun,
                'tanggal_pembayaran' => $payroll->tanggal_pembayaran ? Carbon::parse($payroll->tanggal_pembayaran)->format('d F Y') : '-',
                'metode_pembayaran' => $payroll->metode_pembayaran,
                'status' => $payroll->status,
                'kas_bank' => $payroll->kasBankAkun ? $payroll->kasBankAkun->nama_akun : '-',
                'nomor_jurnal' => $payroll->jurnalUmum ? $payroll->jurnalUmum->nomor_referensi : null,
            ],
            'karyawan' => [
                'id' => $karyawan?->id,
                'nip' => $karyawan?->nip,
                'nik' => $karyawan?->nik,
                'nama_lengkap' => $karyawan?->nama_lengkap,
                'tipe_sdm' => $karyawan?->tipe_sdm,
                'jabatan' => $karyawan?->jabatan,
                'divisi' => $karyawan?->divisi,
                'status_kepegawaian' => $karyawan?->status_kepegawaian,
                'nama_bank' => $karyawan?->nama_bank,
                'nomor_rekening' => $karyawan?->nomor_rekening,
                'atas_nama_rekening' => $karyawan?->atas_nama_rekening,
            ],
            'pendapatan' => [
                'gaji_pokok' => (float) $payroll->gaji_pokok,
                'tunjangan_jabatan' => (float) $payroll->tunjangan_jabatan,
                'tunjangan_kehadiran' => (float) $payroll->tunjangan_kehadiran,
                'tunjangan_keluarga' => (float) $payroll->tunjangan_keluarga,
                'bonus_tambahan' => (float) $payroll->bonus_tambahan,
                'total_pendapatan' => (float) $payroll->total_pendapatan,
            ],
            'potongan' => [
                'potongan_bpjs' => (float) $payroll->potongan_bpjs,
                'potongan_kasbon' => (float) $payroll->potongan_kasbon,
                'potongan_terlambat' => (float) $payroll->potongan_terlambat,
                'potongan_lain' => (float) $payroll->potongan_lain,
                'total_potongan' => (float) $payroll->total_potongan,
            ],
            'gaji_bersih' => (float) $payroll->gaji_bersih,
            'terbilang' => $terbilang,
            'catatan' => $payroll->catatan,
            'approver' => [
                'nama' => $payroll->approver ? $payroll->approver->name : null,
                'tanggal' => $payroll->disetujui_pada ? Carbon::parse($payroll->disetujui_pada)->format('d F Y') : null,
            ],
            'payer' => [
                'nama' => $payroll->payer ? $payroll->payer->name : null,
                'tanggal' => $payroll->dibayar_pada ? Carbon::parse($payroll->dibayar_pada)->format('d F Y') : null,
            ],
        ];

        return $this->successResponse($payload, 'Data slip gaji berhasil dimuat');
    }

    /**
     * Generate unique nomor slip (SLIP-YYYYMM-XXXX).
     */
    protected function generateNomorSlip(int $tahun, int $bulan): string
    {
        $prefix = sprintf('SLIP-%04d%02d-', $tahun, $bulan);
        $count = Payroll::where('periode_tahun', $tahun)->where('periode_bulan', $bulan)->count() + 1;

        do {
            $nomor = sprintf('%s%04d', $prefix, $count);
            $exists = Payroll::where('nomor_slip', $nomor)->exists();
            if ($exists) {
                $count++;
            }
        } while ($exists);

        return $nomor;
    }

    /**
     * Indonesian Terbilang helper for currency.
     */
    public static function terbilangRupiah(float|int $angka): string
    {
        $angka = abs((int) round($angka));
        if ($angka === 0) {
            return 'Nol Rupiah';
        }

        $terbilang = preg_replace('/\s+/', ' ', trim(self::terbilangAngka($angka)));

        return $terbilang . ' Rupiah';
    }

    protected static function terbilangAngka(int $angka): string
    {
        $baca = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
        $hasil = '';

        if ($angka < 12) {
            $hasil = ' ' . $baca[$angka];
        } elseif ($angka < 20) {
            $hasil = self::terbilangAngka($angka - 10) . ' Belas';
        } elseif ($angka < 100) {
            $hasil = self::terbilangAngka((int) ($angka / 10)) . ' Puluh' . self::terbilangAngka($angka % 10);
        } elseif ($angka < 200) {
            $hasil = ' Seratus' . self::terbilangAngka($angka - 100);
        } elseif ($angka < 1000) {
            $hasil = self::terbilangAngka((int) ($angka / 100)) . ' Ratus' . self::terbilangAngka($angka % 100);
        } elseif ($angka < 2000) {
            $hasil = ' Seribu' . self::terbilangAngka($angka - 1000);
        } elseif ($angka < 1000000) {
            $hasil = self::terbilangAngka((int) ($angka / 1000)) . ' Ribu' . self::terbilangAngka($angka % 1000);
        } elseif ($angka < 1000000000) {
            $hasil = self::terbilangAngka((int) ($angka / 1000000)) . ' Juta' . self::terbilangAngka($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            $hasil = self::terbilangAngka((int) ($angka / 1000000000)) . ' Miliar' . self::terbilangAngka($angka % 1000000000);
        } else {
            $hasil = self::terbilangAngka((int) ($angka / 1000000000000)) . ' Triliun' . self::terbilangAngka($angka % 1000000000000);
        }

        return $hasil;
    }
}
