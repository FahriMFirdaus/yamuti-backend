<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $numbers;
    public string $pesan;
    public string $target;

    public function __construct(array $numbers, string $pesan, string $target)
    {
        $this->numbers = $numbers;
        $this->pesan = $pesan;
        $this->target = $target;
        $this->onQueue('low');
    }

    public function handle(): void
    {
        $currentDelaySeconds = 0;
        $total = count($this->numbers);

        foreach ($this->numbers as $number) {
            $stepDelay = rand(3, 5);
            $currentDelaySeconds += $stepDelay;

            SendWhatsAppMessage::dispatch($number, $this->pesan, true)
                ->delay(now()->addSeconds($currentDelaySeconds));
        }

        Log::info("ProcessBroadcastJob: Berhasil menjadwalkan {$total} pesan WA ke queue 'low' untuk target: {$this->target}");
    }
}
