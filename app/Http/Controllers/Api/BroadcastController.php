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
            'target_penerima' => 'required|in:donatur,umum,semua'
        ]);

        $targets = collect();

        // Mode Operasional Nyata
        if ($validated['target_penerima'] === 'donatur' || $validated['target_penerima'] === 'semua') {
            // 1. Ambil nomor dari orang yang pernah berdonasi (Guest/Tamu maupun terdaftar)
            $donatursDariTransaksi = Donasi::whereNotNull('no_whatsapp')->pluck('no_whatsapp');
            $targets = $targets->merge($donatursDariTransaksi);
            
            // 2. Ambil nomor dari Akun User yang sudah mendaftar sebagai 'donatur' (meskipun belum berdonasi)
            $donatursTerdaftar = User::role('donatur')->whereNotNull('no_hp')->pluck('no_hp');
            $targets = $targets->merge($donatursTerdaftar);
        }

        if ($validated['target_penerima'] === 'umum' || $validated['target_penerima'] === 'semua') {
            // Ambil nomor dari masyarakat yang pernah melakukan Kunjungan
            $kunjungans = Kunjungan::whereNotNull('no_whatsapp')->pluck('no_whatsapp');
            $targets = $targets->merge($kunjungans);
        }
        
        if ($validated['target_penerima'] === 'semua') {
            // Jika dikirim ke semua, masukkan juga seluruh akun (termasuk admin)
            $users = User::whereNotNull('no_hp')->pluck('no_hp');
            $targets = $targets->merge($users);
        }

        // Bersihkan dan standarisasi nomor HP ke format yang diterima WA/Fonnte
        $validNumbers = $targets->map(function ($num) {
            $cleaned = preg_replace('/[^0-9]/', '', $num);
            if (str_starts_with($cleaned, '0')) {
                return '62' . substr($cleaned, 1);
            }
            return $cleaned;
        })->filter(function ($num) {
            return strlen($num) >= 10;
        })->unique()->values()->toArray();

        if (empty($validNumbers)) {
            return $this->errorResponse('Tidak ada target nomor HP yang valid untuk dikirim.', 400);
        }

        // Fonnte menerima multiple numbers dipisahkan koma
        $targetString = implode(',', $validNumbers);

        try {
            $token = env('FONNTE_TOKEN', 'Pz37ptpxRHQpUK4WGETN');

            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $targetString,
                'message' => $validated['pesan'],
                'countryCode' => '62', // Otomatis meng-handle format 08 menjadi 628
                'delay' => '5-10' // Anti-ban: Jeda acak 5 sampai 10 detik antar setiap pengiriman pesan
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['status']) && $result['status'] === true) {
                return $this->successResponse([
                    'pesan' => $validated['pesan'],
                    'target_count' => count($validNumbers),
                    'numbers_sent' => $validNumbers,
                    'fonnte_response' => $result
                ], 'Hore! Pesan broadcast berhasil ditembakkan ke WhatsApp via Fonnte.');
            } else {
                Log::error('Fonnte API Error', ['response' => $result]);
                return $this->errorResponse('Gagal mengirim pesan dari sisi provider Fonnte', 500, $result);
            }

        } catch (\Exception $e) {
            Log::error('Broadcast Exception: ' . $e->getMessage());
            return $this->errorResponse('Terjadi kesalahan sistem saat menghubungi Fonnte', 500, $e->getMessage());
        }
    }
}
