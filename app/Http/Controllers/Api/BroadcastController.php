<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Models\Donasi;
use App\Models\Kunjungan;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BroadcastController extends Controller
{
    use ApiResponse;

    /**
     * Helper untuk mengumpulkan dan menstandarisasi nomor telepon unik dari target penerima.
     */
    protected function getTargetNumbers(string $targetPenerima): array
    {
        $targets = collect();

        if ($targetPenerima === 'donatur' || $targetPenerima === 'semua') {
            // 1. Ambil nomor dari orang yang pernah berdonasi
            $donatursDariTransaksi = Donasi::whereNotNull('no_whatsapp')->pluck('no_whatsapp');
            $targets = $targets->merge($donatursDariTransaksi);

            // 2. Ambil nomor dari Akun User yang sudah mendaftar sebagai 'donatur'
            $donatursTerdaftar = User::role('donatur')->whereNotNull('no_hp')->pluck('no_hp');
            $targets = $targets->merge($donatursTerdaftar);
        }

        if ($targetPenerima === 'kunjungan' || $targetPenerima === 'semua') {
            $kunjungans = Kunjungan::whereNotNull('no_whatsapp')->pluck('no_whatsapp');
            $targets = $targets->merge($kunjungans);
        }

        if ($targetPenerima === 'semua') {
            $users = User::whereNotNull('no_hp')->pluck('no_hp');
            $targets = $targets->merge($users);
        }

        // Bersihkan dan standarisasi nomor HP ke format 62xxx
        return $targets->map(function ($num) {
            $cleaned = preg_replace('/[^0-9]/', '', (string) $num);
            if (str_starts_with($cleaned, '0')) {
                return '62'.substr($cleaned, 1);
            }

            return $cleaned;
        })->filter(function ($num) {
            return strlen($num) >= 10;
        })->unique()->values()->toArray();
    }

    /**
     * Endpoint untuk preview estimasi jumlah nomor unik valid sebelum broadcast dikirim.
     */
    public function preview(Request $request): JsonResponse
    {
        $target = $request->query('target', 'donatur');
        if (! in_array($target, ['donatur', 'kunjungan', 'semua'])) {
            $target = 'donatur';
        }

        $validNumbers = $this->getTargetNumbers($target);

        return $this->successResponse([
            'target' => $target,
            'total_target' => count($validNumbers),
        ], 'Estimasi jumlah penerima broadcast berhasil dihitung.');
    }

    /**
     * Mengirim pesan broadcast ke queue background dengan delay bertingkat anti-banned.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pesan' => 'required|string',
            'target_penerima' => 'required|in:donatur,kunjungan,semua',
        ]);

        $validNumbers = $this->getTargetNumbers($validated['target_penerima']);

        if (empty($validNumbers)) {
            return $this->errorResponse('Tidak ada target nomor HP yang valid untuk dikirim.', 400);
        }

        $currentDelaySeconds = 0;
        $totalQueued = 0;

        foreach ($validNumbers as $number) {
            // Tambahkan delay acak 3-5 detik per pesan agar aman dari rate limit & anti-spam WA
            $stepDelay = rand(3, 5);
            $currentDelaySeconds += $stepDelay;

            SendWhatsAppMessage::dispatch($number, $validated['pesan'], true)
                ->delay(now()->addSeconds($currentDelaySeconds));

            $totalQueued++;
        }

        Log::info("Broadcast WhatsApp dijadwalkan ke queue untuk {$totalQueued} kontak target: {$validated['target_penerima']}");

        return $this->successResponse([
            'pesan' => $validated['pesan'],
            'total_target' => $totalQueued,
            'berhasil' => $totalQueued,
            'gagal' => 0,
            'status' => 'QUEUED',
            'estimasi_selesai_menit' => ceil($currentDelaySeconds / 60),
        ], "Alhamdulillah! Pesan broadcast dijadwalkan ke antrean untuk {$totalQueued} nomor penerima (estimasi selesai ~".ceil($currentDelaySeconds / 60).' menit).');
    }
}
