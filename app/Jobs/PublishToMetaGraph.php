<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublishToMetaGraph implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $message;
    public $imageUrl;

    /**
     * Create a new job instance.
     *
     * @param string $message
     * @param string|null $imageUrl
     */
    public function __construct(string $message, ?string $imageUrl = null)
    {
        $this->message = $message;
        $this->imageUrl = $imageUrl;
        
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $accessToken = env('META_GRAPH_ACCESS_TOKEN');
        $pageId = env('META_PAGE_ID');

        if (!$accessToken || !$pageId) {
            Log::warning('Meta Graph API credentials not configured.');
            return;
        }

        $endpoint = $this->imageUrl 
            ? "https://graph.facebook.com/v19.0/{$pageId}/photos"
            : "https://graph.facebook.com/v19.0/{$pageId}/feed";

        $payload = [
            'access_token' => $accessToken,
            'message' => $this->message,
        ];

        if ($this->imageUrl) {
            $payload['url'] = $this->imageUrl;
        }

        try {
            $response = Http::timeout(10)->post($endpoint, $payload);

            if (!$response->successful()) {
                Log::error('Gagal mempublikasikan ke Meta Graph', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                $this->fail(new \Exception('Meta API returned error: ' . $response->status()));
            }
        } catch (\Exception $e) {
            Log::error('Exception saat mempublikasikan ke Meta', [
                'error' => $e->getMessage()
            ]);
            $this->fail($e);
        }
    }
}
