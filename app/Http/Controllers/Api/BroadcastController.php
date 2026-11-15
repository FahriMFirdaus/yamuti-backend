<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
use App\Models\Kunjungan;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BroadcastController extends Controller
{
    use ApiResponse;

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pesan' => 'required|string',
            'target_penerima' => 'required|in:donatur,kunjungan,semua',
        ]);

        $targets = collect();

        // Mode Operasional Nyata
        if ($validated['target_penerima'] === 'donatur' || $validated['target_penerima'] === 'semua') {
            // 1. Ambil nomor dari orang yang pernah berdonasi
            $donatursDariTransaksi = Donasi::whereNotNull('no_whatsapp')->pluck('no_whatsapp');
            $targets = $targets->merge($donatursDariTransaksi);

            // 2. Ambil nomor dari Akun User yang sudah mendaftar sebagai 'donatur'
            $donatursTerdaftar = User::role('donatur')->whereNotNull('no_hp')->pluck('no_hp');
            $targets = $targets->merge($donatursTerdaftar);
        }

        if ($validated['target_penerima'] === 'kunjungan' || $validated['target_penerima'] === 'semua') {
            $kunjungans = Kunjungan::whereNotNull('no_whatsapp')->pluck('no_whatsapp');
            $targets = $targets->merge($kunjungans);
        }

        if ($validated['target_penerima'] === 'semua') {
            $users = User::whereNotNull('no_hp')->pluck('no_hp');
            $targets = $targets->merge($users);
        }

        // Bersihkan dan standarisasi nomor HP
        $validNumbers = $targets->map(function ($num) {
            $cleaned = preg_replace('/[^0-9]/', '', $num);
            if (str_starts_with($cleaned, '0')) {
                return '62'.substr($cleaned, 1);
            }

            return $cleaned;
        })->filter(function ($num) {
            return strlen($num) >= 10;
        })->unique()->values()->toArray();

        if (empty($validNumbers)) {
            return $this->errorResponse('Tidak ada target nomor HP yang valid untuk dikirim.', 400);
        }

        $gatewayUrl = config('services.whatsapp.url', env('WHATSAPP_GATEWAY_URL', 'http://shared_wa_gateway:3000'));
        $endpoint = config('services.whatsapp.endpoint', env('WHATSAPP_GATEWAY_ENDPOINT', '/api/send-message'));
        $sessionId = config('services.whatsapp.session_id', env('WHATSAPP_SESSION_ID', 'yamuti'));

        if (! $gatewayUrl) {
            return $this->errorResponse('Konfigurasi WHATSAPP_GATEWAY_URL belum diatur.', 500);
        }

        $successCount = 0;
        $failedCount = 0;
        $url = rtrim($gatewayUrl, '/').$endpoint;

        foreach ($validNumbers as $number) {
            try {
                $payload = [
                    'number' => $number,
                    'message' => $validated['pesan'],
                ];
                if ($sessionId) {
                    $payload['sessionId'] = $sessionId;
                }

                $response = Http::timeout(10)->post($url, $payload);
                if ($response->successful() && ($response->json('status') === true || $response->json('success') === true)) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
                Log::error("Broadcast error to {$number}: ".$e->getMessage());
            }
        }

        return $this->successResponse([
            'pesan' => $validated['pesan'],
            'total_target' => count($validNumbers),
            'berhasil' => $successCount,
            'gagal' => $failedCount,
        ], "Pesan broadcast diproses. Berhasil: {$successCount}, Gagal/Pending: {$failedCount}");
    }
}
