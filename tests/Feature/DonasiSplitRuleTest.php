<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Donasi;
use App\Models\TransaksiKeuangan;
use App\Services\DonasiService;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\Queue;

class DonasiSplitRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_donasi_paid_triggers_split_rule()
    {
        Queue::fake();

        // 1. Arrange: Buat Data Donasi Dummy
        $donasi = Donasi::create([
            'nama_donatur' => 'Hamba Allah',
            'no_whatsapp' => '08123456789',
            'gross_amount' => 1000000,
            'payment_type' => 'bank_transfer',
            'status' => 'PENDING',
        ]);

        // 2. Act: Panggil Service untuk mengubah status jadi PAID
        $service = app(DonasiService::class);
        $service->updateStatus($donasi->id, 'PAID');

        // 3. Assert: Pastikan ada 2 record di TransaksiKeuangan (1 Pusat, 1 Cabang)
        $this->assertDatabaseCount('transaksi_keuangans', 2);

        // Pusat 10% dari 1 juta = 100000
        $this->assertDatabaseHas('transaksi_keuangans', [
            'jenis_kas' => 'Pusat',
            'tipe_transaksi' => 'Debit',
            'nominal' => 100000,
            'donasi_id' => $donasi->id,
        ]);

        // Cabang 90% dari 1 juta = 900000
        $this->assertDatabaseHas('transaksi_keuangans', [
            'jenis_kas' => 'Cabang',
            'tipe_transaksi' => 'Debit',
            'nominal' => 900000,
            'donasi_id' => $donasi->id,
        ]);

        // Assert: Job WA Notifikasi Donasi Sukses didispatch
        Queue::assertPushed(SendWhatsAppMessage::class);
    }
}
