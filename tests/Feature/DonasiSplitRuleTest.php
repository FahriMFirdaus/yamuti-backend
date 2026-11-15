<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Donasi;
use App\Models\TransaksiKeuangan;
use App\Services\DonasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

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

        // 3. Assert: Pastikan ada 4 record di TransaksiKeuangan sesuai default split rule
        $this->assertDatabaseCount('transaksi_keuangans', 4);

        // Yayasan Tasikmalaya 75% dari 1 juta = 750000
        $this->assertDatabaseHas('transaksi_keuangans', [
            'jenis_kas' => 'Yayasan Tasikmalaya',
            'tipe_transaksi' => 'Debit',
            'nominal' => 750000,
            'donasi_id' => $donasi->id,
        ]);

        // Operasional Website 10% dari 1 juta = 100000
        $this->assertDatabaseHas('transaksi_keuangans', [
            'jenis_kas' => 'Operasional Website',
            'tipe_transaksi' => 'Debit',
            'nominal' => 100000,
            'donasi_id' => $donasi->id,
        ]);

        // Assert: Job WA Notifikasi Donasi Sukses didispatch
        Queue::assertPushed(SendWhatsAppMessage::class);
    }
}
