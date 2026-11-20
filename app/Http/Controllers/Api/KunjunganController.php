<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KunjunganRequest;
use App\Models\Kunjungan;
use App\Models\Setting;
use App\Services\KunjunganService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class KunjunganController extends Controller
{
    use ApiResponse;

    protected KunjunganService $kunjunganService;

    public function __construct(KunjunganService $kunjunganService)
    {
        $this->kunjunganService = $kunjunganService;
    }

    // Endpoint Publik
    public function store(KunjunganRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['is_full_day'] = (bool) $request->boolean('is_full_day');

        // Jika request membawa token user atau terotentikasi sanctum, tautkan user_id
        $user = $request->user('sanctum');
        if ($user) {
            $validatedData['user_id'] = $user->id;
        }

        $data = $this->kunjunganService->requestKunjungan($validatedData);

        return $this->successResponse($data, 'Permintaan kunjungan berhasil diajukan, menunggu persetujuan admin', 201);
    }

    public function ketersediaan(Request $request): JsonResponse
    {
        $tanggal = $request->query('tanggal');
        if (! $tanggal) {
            return $this->errorResponse('Parameter tanggal wajib diisi (Format: YYYY-MM-DD)', 400);
        }

        // Ambil jam operasional dari settings, atau gunakan default
        $setting = Setting::where('key', 'jam_operasional_kunjungan')->first();
        $defaultSlots = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00'];
        $slots = $setting ? $setting->value : $defaultSlots;

        // Ambil kunjungan yang sudah dibooking pada tanggal tersebut (hindari status REJECTED atau DIBATALKAN)
        $bookedKunjungan = Kunjungan::whereDate('slot_waktu', $tanggal)
            ->whereNotIn('status', ['REJECTED', 'DIBATALKAN'])
            ->get();

        // Cek apakah ada booking FULL DAY
        $hasFullDayBooking = $bookedKunjungan->contains('is_full_day', true);

        // Ekstrak jam yang sudah dibooking parsial (H:i)
        $bookedTimes = $bookedKunjungan->where('is_full_day', false)->map(function ($k) {
            return $k->slot_waktu->format('H:i');
        })->toArray();

        // Bentuk response
        $ketersediaan = [];

        // Slot khusus Full Day
        $ketersediaan[] = [
            'jam' => 'FULL DAY',
            'is_available' => ! $hasFullDayBooking && count($bookedTimes) === 0,
        ];

        // Slot per jam
        foreach ($slots as $slot) {
            $ketersediaan[] = [
                'jam' => $slot,
                'is_available' => ! $hasFullDayBooking && ! in_array($slot, $bookedTimes),
            ];
        }

        // Tambahkan jam khusus (custom) yang sudah dibooking agar muncul di UI sebagai "Penuh/Disabled"
        foreach ($bookedTimes as $bookedTime) {
            if (! in_array($bookedTime, $slots)) {
                $ketersediaan[] = [
                    'jam' => $bookedTime.' (Acara Khusus)',
                    'is_available' => false,
                ];
            }
        }

        return $this->successResponse([
            'tanggal' => $tanggal,
            'slot_waktu' => $ketersediaan,
        ], 'Data ketersediaan jadwal berhasil diambil');
    }

    // Endpoint Terlindungi
    public function index(Request $request): JsonResponse
    {
        $data = $this->kunjunganService->getAllKunjungan((int) $request->query('per_page', 15));

        return $this->successResponse($data, 'Data kunjungan berhasil diambil');
    }

    public function show($id): JsonResponse
    {
        $data = $this->kunjunganService->getKunjunganById($id);

        return $this->successResponse($data, 'Detail kunjungan berhasil diambil');
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:APPROVED,REJECTED,COMPLETED',
            'catatan_admin' => 'nullable|string|max:1000',
        ]);

        $status = strtoupper($validated['status']);
        $catatanAdmin = $validated['catatan_admin'] ?? null;
        $adminId = $request->user()->id;

        if ($status === 'APPROVED') {
            $data = $this->kunjunganService->approveKunjungan($id, $adminId, $catatanAdmin);

            return $this->successResponse($data, 'Kunjungan disetujui');
        } elseif ($status === 'REJECTED') {
            $data = $this->kunjunganService->rejectKunjungan($id, $adminId, $catatanAdmin);

            return $this->successResponse($data, 'Kunjungan ditolak');
        } else {
            $kunjungan = Kunjungan::findOrFail($id);
            $kunjungan->update([
                'status' => $status,
                'catatan_admin' => $catatanAdmin,
            ]);

            return $this->successResponse($kunjungan, "Kunjungan diperbarui menjadi $status");
        }
    }

    public function riwayat(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;
        $noHp = $user->no_hp;

        // Cari berdasarkan user_id secara langsung, atau fallback ke no_whatsapp jika user_id belum terisi di masa lalu
        $query = Kunjungan::where(function ($q) use ($userId, $noHp) {
            $q->where('user_id', $userId);
            if (! empty($noHp)) {
                $q->orWhere('no_whatsapp', $noHp);
            }
        });

        $data = $query->latest()->paginate(15);

        return $this->successResponse($data, 'Riwayat kunjungan berhasil diambil');
    }
}
