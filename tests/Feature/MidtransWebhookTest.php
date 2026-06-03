<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Donasi;
use App\Services\DonasiService;
use Mockery;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_with_valid_signature_updates_status_to_paid()
    {
        // 1. Arrange
        $donasi = Donasi::create([
            'nama_donatur' => 'John Doe',
            'no_whatsapp' => '08123456789',
            'gross_amount' => 500000,
            'payment_type' => 'qris',
            'status' => 'PENDING',
        ]);

        $serverKey = config('services.midtrans.server_key');
        $orderId = $donasi->id;
        $statusCode = '200';
        $grossAmount = '500000.00';
        $transactionStatus = 'settlement';

        $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $signatureKey,
            'transaction_status' => $transactionStatus,
        ];

        // 2. Mocking DonasiService untuk menghindari insert DB sungguhan
        // Karena kita hanya ingin test API Response & Signature Check
        $mockService = Mockery::mock(DonasiService::class);
        $mockService->shouldReceive('updateStatus')
            ->once()
            ->with($orderId, 'PAID');
            
        $this->app->instance(DonasiService::class, $mockService);

        // 3. Act
        $response = $this->postJson('/api/midtrans/webhook', $payload);

        // 4. Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Webhook processed successfully']);
    }

    public function test_webhook_with_invalid_signature_returns_403()
    {
        // 1. Arrange
        $payload = [
            'order_id' => 'dummy-order',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'invalid-signature',
            'transaction_status' => 'settlement',
        ];

        // 2. Act
        $response = $this->postJson('/api/midtrans/webhook', $payload);

        // 3. Assert
        $response->assertStatus(403);
        $response->assertJson(['message' => 'Invalid signature']);
    }
}
