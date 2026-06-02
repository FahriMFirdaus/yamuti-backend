<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$clientKey = config('services.midtrans.client_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    public function createTransaction(array $transactionData): array
    {
        $snapToken = Snap::getSnapToken($transactionData);
        // Dalam mode non-production URL mengarah ke sandbox
        $paymentUrl = 'https://app.' . (Config::$isProduction ? '' : 'sandbox.') . 'midtrans.com/snap/v2/vtweb/' . $snapToken;

        return [
            'snap_token' => $snapToken,
            'payment_url' => $paymentUrl
        ];
    }
}
