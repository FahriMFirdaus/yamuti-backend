<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BroadcastController extends Controller
{
    use ApiResponse;

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pesan' => 'required|string',
            'target_penerima' => 'required|in:donatur,umum,semua'
        ]);

        // Simulasi pengiriman broadcast message
        // Logika aslinya akan mengambil data nomor telepon berdasarkan target_penerima, 
        // lalu memanggil service WhatsApp gateway atau Email.

        return $this->successResponse([
            'pesan' => $validated['pesan'],
            'target' => $validated['target_penerima'],
            'status' => 'queued'
        ], 'Pesan broadcast sedang diproses untuk dikirim');
    }
}
