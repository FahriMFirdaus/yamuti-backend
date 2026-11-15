<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phone;

    public $message;

    public $isBroadcast;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phone, string $message, bool $isBroadcast = false)
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->isBroadcast = $isBroadcast;

        // Set queue priority
        $this->onQueue($isBroadcast ? 'low' : 'high');
    }

    /**
     * Normalisasi nomor telepon ke format internasional (misal 0812 -> 62812).
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Bersihkan semua karakter selain angka
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Jika diawali dengan 0, ubah ke 62
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $gatewayUrl = config('services.whatsapp.url', env('WHATSAPP_GATEWAY_URL', 'http://shared_wa_gateway:3000'));
        $endpoint = config('services.whatsapp.endpoint', env('WHATSAPP_GATEWAY_ENDPOINT', '/api/send-message'));
        $sessionId = config('services.whatsapp.session_id', env('WHATSAPP_SESSION_ID', 'yamuti'));

        $formattedPhone = $this->formatPhoneNumber($this->phone);

        if (! $gatewayUrl) {
            Log::info("WHATSAPP_GATEWAY_URL belum dikonfigurasi. Simulasi kirim WA ke {$formattedPhone}: {$this->message}");

            return;
        }

        try {
            $payload = [
                'number' => $formattedPhone,
                'message' => $this->message,
            ];
            if ($sessionId) {
                $payload['sessionId'] = $sessionId;
            }

            $url = rtrim($gatewayUrl, '/').$endpoint;
            $response = Http::timeout(10)->post($url, $payload);

            if ($response->successful() && ($response->json('status') === true || $response->json('success') === true)) {
                Log::info("✅ WA Gateway: Pesan berhasil terkirim ke {$formattedPhone}");
            } else {
                Log::warning("⚠️ WA Gateway Gagal ke {$formattedPhone} | Respons: ".$response->body());
            }
        } catch (\Exception $e) {
            Log::error("❌ Exception WA Gateway saat mengirim ke {$formattedPhone}: ".$e->getMessage());
        }
    }
}
