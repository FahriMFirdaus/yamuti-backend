<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\TransaksiKeuangan;
use Spatie\Permission\Models\Role;

class TransaksiKeuanganTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    }

    public function test_admin_can_record_transaksi()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/transaksi', [
            'jenis_kas' => 'Pusat',
            'tipe_transaksi' => 'Debit',
            'nominal' => 1000000,
            'deskripsi' => 'Sumbangan tunai'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transaksi_keuangans', ['nominal' => 1000000]);
    }
}
