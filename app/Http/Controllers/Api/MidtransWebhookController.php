<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DonasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    protected DonasiService $donasiService;

    public function __construct(DonasiService $donasiService)
    {
        $this->donasiService = $donasiService;
    }

    public function handle(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        
        $orderId = $request->order_id;
        $statusCode = $request->status_code;
        $grossAmount = $request->gross_amount;
        $signatureKey = $request->signature_key;
        $transactionStatus = $request->transaction_status;

        // Verifikasi Signature
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        
        if ($expectedSignature !== $signatureKey) {
            Log::warning("Midtrans Webhook Invalid Signature for Order: $orderId");
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        Log::info("Midtrans Webhook Received for Order: $orderId, Status: $transactionStatus");

        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $this->donasiService->updateStatus($orderId, 'PAID');
        } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $this->donasiService->updateStatus($orderId, 'FAILED');
        }

        return response()->json(['message' => 'Webhook processed successfully']);
    }
}
