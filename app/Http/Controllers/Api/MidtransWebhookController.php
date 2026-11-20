<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
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
        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if ($expectedSignature !== $signatureKey) {
            Log::warning("Midtrans Webhook Invalid Signature for Order: $orderId");

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        Log::info("Midtrans Webhook Received for Order: $orderId, Status: $transactionStatus");

        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $status = 'PAID';
        } elseif ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $status = 'FAILED';
        } else {
            return response()->json(['message' => 'Status ignored']);
        }

        if (str_starts_with($orderId, 'GRP-') || str_starts_with($orderId, 'POTA-')) {
            $donasis = Donasi::where('payment_group_id', $orderId)->get();
            foreach ($donasis as $donasi) {
                if ($status === 'PAID') {
                    $donasi->update([
                        'transaction_id' => $request->transaction_id,
                        'payment_type' => $request->payment_type,
                    ]);
                }
                $this->donasiService->updateStatus($donasi->id, $status);
            }
        } else {
            $donasi = Donasi::find($orderId);
            if ($donasi) {
                if ($status === 'PAID') {
                    $donasi->update([
                        'transaction_id' => $request->transaction_id,
                        'payment_type' => $request->payment_type,
                    ]);
                }
                $this->donasiService->updateStatus($orderId, $status);
            }
        }

        return response()->json(['message' => 'Webhook processed successfully']);
    }
}
