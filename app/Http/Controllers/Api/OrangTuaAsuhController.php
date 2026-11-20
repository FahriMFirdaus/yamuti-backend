<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrangTuaAsuh;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrangTuaAsuhController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = OrangTuaAsuh::with(['anakAsuh.records', 'user:id,name,email'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_donatur', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%")
                    ->orWhereHas('anakAsuh', function ($sub) use ($search) {
                        $sub->where('nama', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) $request->query('per_page', 15);
        $data = $query->paginate($perPage);

        return $this->successResponse($data, 'Data orang tua asuh berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'nama_donatur' => 'required|string|max:255',
            'no_hp' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'anak_asuh_id' => 'required|exists:anak_asuhs,id',
            'nominal_bulanan' => 'required|numeric|min:10000',
            'status' => 'required|in:Aktif,Jeda,Selesai',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'hari_tagihan_per_bulan' => 'required|integer|min:1|max:31',
            'catatan_doa' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id ?? null;
        $orangTuaAsuh = OrangTuaAsuh::create($validated);

        return $this->successResponse($orangTuaAsuh->load('anakAsuh'), 'Orang tua asuh berhasil didaftarkan', 201);
    }

    public function show($id): JsonResponse
    {
        $data = OrangTuaAsuh::with(['anakAsuh', 'user'])->findOrFail($id);

        return $this->successResponse($data, 'Detail orang tua asuh berhasil diambil');
    }

    public function update(Request $request, $id): JsonResponse
    {
        $orangTuaAsuh = OrangTuaAsuh::findOrFail($id);

        $validated = $request->validate([
            'nama_donatur' => 'sometimes|string|max:255',
            'no_hp' => 'sometimes|string|max:30',
            'email' => 'nullable|email|max:255',
            'anak_asuh_id' => 'sometimes|exists:anak_asuhs,id',
            'nominal_bulanan' => 'sometimes|numeric|min:10000',
            'status' => 'sometimes|in:Aktif,Jeda,Selesai',
            'tanggal_mulai' => 'sometimes|date',
            'tanggal_selesai' => 'nullable|date',
            'hari_tagihan_per_bulan' => 'sometimes|integer|min:1|max:31',
            'catatan_doa' => 'nullable|string',
        ]);

        $validated['updated_by'] = $request->user()->id ?? null;
        $orangTuaAsuh->update($validated);

        return $this->successResponse($orangTuaAsuh->load('anakAsuh'), 'Data orang tua asuh berhasil diperbarui');
    }

    public function destroy($id): JsonResponse
    {
        $orangTuaAsuh = OrangTuaAsuh::findOrFail($id);
        $orangTuaAsuh->delete();

        return $this->successResponse(null, 'Data komitmen orang tua asuh berhasil dihapus');
    }

    /**
     * Endpoint khusus bagi user/donatur terotentikasi untuk melihat data komitmen POTA miliknya sendiri.
     */
    public function saya(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = OrangTuaAsuh::with([
            'anakAsuh:id,nama,nik,jenis_kelamin,tanggal_lahir,tempat_lahir,tingkat_pendidikan,nama_sekolah,status,foto_identitas,status_keluarga,keterangan,tinggi_badan,berat_badan,riwayat_penyakit,status_imunisasi',
            'anakAsuh.records' => function ($q) {
                $q->latest('tanggal_kejadian');
            },
            'donasis' => function ($q) {
                $q->where('status', 'PAID')
                    ->latest();
            }
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        // Hitung status lunas bulan berjalan untuk tiap ananda binaan
        $now = now();
        $data->each(function ($pota) use ($now) {
            $paidThisMonth = $pota->donasis->first(function ($d) use ($now) {
                return $d->created_at && $d->created_at->month === $now->month && $d->created_at->year === $now->year;
            });

            $pota->is_paid_this_month = (bool) $paidThisMonth;
            $pota->status_bulan_ini = $paidThisMonth ? 'lunas' : 'menunggu';
            $pota->last_paid_at = $paidThisMonth ? $paidThisMonth->created_at->toIso8601String() : null;
        });

        return $this->successResponse($data, 'Data anak asuh binaan berhasil diambil');
    }

    /**
     * Endpoint bagi user/donatur terotentikasi untuk mendaftarkan komitmen POTA baru secara mandiri.
     * Mendukung pendaftaran 1 anak maupun banyak anak (multi-select) sekaligus.
     */
    public function daftarMandiri(Request $request): JsonResponse
    {
        $user = $request->user();

        // Cek apakah request berupa array 'items' (multi-anak) atau single
        if ($request->has('items') && is_array($request->input('items'))) {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.anak_asuh_id' => 'required|exists:anak_asuhs,id',
                'items.*.nominal_bulanan' => 'required|numeric|min:50000',
                'items.*.hari_tagihan_per_bulan' => 'required|integer|min:1|max:31',
                'items.*.catatan_doa' => 'nullable|string|max:500',
            ]);

            $created = [];
            \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $user, &$created) {
                foreach ($validated['items'] as $item) {
                    $anak = \App\Models\AnakAsuh::lockForUpdate()->find($item['anak_asuh_id']);
                    if (! $anak) continue;

                    // Validasi: Cek apakah donatur ini sudah mendaftar anak ini
                    $existsSelf = OrangTuaAsuh::where('anak_asuh_id', $item['anak_asuh_id'])
                        ->where('status', 'Aktif')
                        ->where('user_id', $user->id)
                        ->exists();

                    if (! $existsSelf) {
                        $pota = OrangTuaAsuh::create([
                            'user_id' => $user->id,
                            'nama_donatur' => $user->name,
                            'no_hp' => $user->no_hp ?: '',
                            'email' => $user->email,
                            'anak_asuh_id' => $item['anak_asuh_id'],
                            'nominal_bulanan' => $item['nominal_bulanan'],
                            'status' => 'Aktif',
                            'tanggal_mulai' => now()->toDateString(),
                            'hari_tagihan_per_bulan' => $item['hari_tagihan_per_bulan'],
                            'catatan_doa' => $item['catatan_doa'] ?? null,
                            'created_by' => $user->id,
                        ]);
                        $created[] = $pota;
                    }
                }
            });

            return $this->successResponse($created, 'Alhamdulillah! Komitmen Orang Tua Asuh untuk '.count($created).' ananda berhasil didaftarkan.', 201);
        }

        $validated = $request->validate([
            'anak_asuh_id' => 'required|exists:anak_asuhs,id',
            'nominal_bulanan' => 'required|numeric|min:50000',
            'hari_tagihan_per_bulan' => 'required|integer|min:1|max:31',
            'catatan_doa' => 'nullable|string|max:500',
        ]);

        // Cek apakah donatur ini sudah menjadi orang tua asuh untuk anak ini dan masih aktif
        $exists = OrangTuaAsuh::where('anak_asuh_id', $validated['anak_asuh_id'])
            ->where('status', 'Aktif')
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return $this->errorResponse('Anda sudah terdaftar sebagai Orang Tua Asuh aktif untuk ananda ini.', 422);
        }

        $pota = OrangTuaAsuh::create([
            'user_id' => $user->id,
            'nama_donatur' => $user->name,
            'no_hp' => $user->no_hp ?: '',
            'email' => $user->email,
            'anak_asuh_id' => $validated['anak_asuh_id'],
            'nominal_bulanan' => $validated['nominal_bulanan'],
            'status' => 'Aktif',
            'tanggal_mulai' => now()->toDateString(),
            'hari_tagihan_per_bulan' => $validated['hari_tagihan_per_bulan'],
            'catatan_doa' => $validated['catatan_doa'] ?? null,
            'created_by' => $user->id,
        ]);

        return $this->successResponse($pota->load('anakAsuh'), 'Alhamdulillah! Komitmen Orang Tua Asuh berhasil didaftarkan.', 201);
    }

    /**
     * Endpoint untuk mengambil daftar anak asuh yang tersedia untuk dipilih oleh donatur.
     */
    public function availableAnakAsuh(Request $request): JsonResponse
    {
        // Ambil anak asuh aktif yang belum memiliki orang tua asuh aktif beserta bio & foto
        $data = \App\Models\AnakAsuh::where('status', 'Aktif')
            ->select('id', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'tingkat_pendidikan', 'nama_sekolah', 'foto_identitas', 'keterangan', 'status_keluarga')
            ->whereDoesntHave('orangTuaAsuh', function ($q) {
                $q->where('status', 'Aktif');
            })
            ->latest()
            ->take(30)
            ->get();

        return $this->successResponse($data, 'Daftar calon anak asuh binaan berhasil diambil');
    }

    /**
     * Endpoint untuk membayar beasiswa POTA secara kolektif untuk banyak anak asuh sekaligus.
     * Membuat donasi terpisah per anak dengan payment_group_id yang sama dan mengembalikan 1 snap_token Midtrans.
     */
    public function bayarKolektif(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'pota_ids' => 'required|array|min:1',
            'pota_ids.*' => 'required|exists:orang_tua_asuhs,id',
            'total_nominal' => 'required|numeric|min:10000',
            'pesan_doa' => 'nullable|string|max:500',
        ]);

        $potasList = OrangTuaAsuh::with('anakAsuh')
            ->whereIn('id', $validated['pota_ids'])
            ->where('user_id', $user->id)
            ->get();

        if ($potasList->isEmpty()) {
            return $this->errorResponse('Tidak ada data komitmen anak asuh yang valid.', 404);
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($potasList, $validated, $user) {
            $paymentGroupId = 'POTA-'.\Illuminate\Support\Str::uuid()->toString();
            $totalAmount = (float) $validated['total_nominal'];
            $count = $potasList->count();

            // Bagikan nominal secara proporsional atau merata per anak
            $perChildAmount = floor($totalAmount / $count);
            $remainder = $totalAmount - ($perChildAmount * $count);

            $itemDetails = [];
            $donasis = [];

            foreach ($potasList as $index => $pota) {
                // Tambahkan sisa pembulatan ke item pertama
                $childNominal = $perChildAmount + ($index === 0 ? $remainder : 0);
                $namaAnak = $pota->anakAsuh?->nama ?? 'Anak Asuh';

                $donasi = \App\Models\Donasi::create([
                    'payment_group_id' => $paymentGroupId,
                    'user_id' => $user->id,
                    'orang_tua_asuh_id' => $pota->id,
                    'nama_donatur' => $user->name,
                    'no_whatsapp' => $user->no_hp ?: '',
                    'pesan' => "[Beasiswa Rutin POTA - Ananda {$namaAnak}] ".($validated['pesan_doa'] ?? ''),
                    'gross_amount' => $childNominal,
                    'status' => 'PENDING',
                    'is_anonymous' => false,
                ]);

                $donasis[] = $donasi;

                $itemDetails[] = [
                    'id' => $donasi->id,
                    'price' => (int) $childNominal,
                    'quantity' => 1,
                    'name' => \Illuminate\Support\Str::limit("Beasiswa POTA: {$namaAnak}", 50),
                ];
            }

            // Integrasi Midtrans dengan Order ID = payment_group_id
            $midtransService = app(\App\Services\MidtransService::class);
            $midtransData = $midtransService->createTransaction([
                'transaction_details' => [
                    'order_id' => $paymentGroupId,
                    'gross_amount' => $totalAmount,
                ],
                'item_details' => $itemDetails,
                'customer_details' => [
                    'first_name' => $user->name,
                    'phone' => $user->no_hp ?: '',
                    'email' => $user->email,
                ],
            ]);

            \App\Models\Donasi::where('payment_group_id', $paymentGroupId)->update([
                'snap_token' => $midtransData['snap_token'],
                'payment_url' => $midtransData['payment_url'],
            ]);

            // Kirim notifikasi WhatsApp jika user memiliki nomor WhatsApp
            if ($user->no_hp) {
                $formattedAmount = 'Rp'.number_format($totalAmount, 0, ',', '.');
                $names = $potasList->map(fn ($p) => $p->anakAsuh?->nama)->filter()->join(', ');
                $pesan = "Halo kak *{$user->name}*,\n\nTerima kasih telah mengajukan pembayaran beasiswa kolektif POTA untuk {$count} ananda ({$names}) sebesar *{$formattedAmount}*.\n\nKode Tagihan: *#{$paymentGroupId}*\nStatus: *Menunggu Pembayaran*\n\nSilakan selesaikan pembayaran melalui tautan berikut:\n{$midtransData['payment_url']}\n\nJazakumullah khairan katsiran.";
                \App\Jobs\SendWhatsAppMessage::dispatch($user->no_hp, $pesan);
            }

            return $this->successResponse([
                'payment_group_id' => $paymentGroupId,
                'snap_token' => $midtransData['snap_token'],
                'payment_url' => $midtransData['payment_url'],
                'total_amount' => $totalAmount,
                'items_count' => $count,
            ], 'Tagihan pembayaran beasiswa kolektif POTA berhasil dibuat.');
        });
    }
}
