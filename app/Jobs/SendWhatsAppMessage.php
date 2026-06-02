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
     *
     * @param string $phone
     * @param string $message
     * @param bool $isBroadcast
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
     * Execute the job.
     */
    public function handle(): void
    {
        // Hubungi Microservice Baileys Node.js
        // Diasumsikan microservice berjalan di internal jaringan atau via URL tertentu
        $baileysUrl = env('WA_MICROSERVICE_URL', 'http://localhost:3000');
        
        try {
            $response = Http::timeout(10)->post("{$baileysUrl}/send-message", [
                'phone' => $this->phone,
                'message' => $this->message,
            ]);

            if (!$response->successful()) {
                Log::error('Gagal mengirim WhatsApp', [
                    'phone' => $this->phone,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                $this->fail(new \Exception('WA Microservice returned error: ' . $response->status()));
            }
        } catch (\Exception $e) {
            Log::error('Exception saat mengirim WhatsApp', [
                'phone' => $this->phone,
                'error' => $e->getMessage()
            ]);
            $this->fail($e);
        }
    }
}
