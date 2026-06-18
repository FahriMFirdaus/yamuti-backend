<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Inventaris;
use Spatie\Permission\Models\Role;

class InventarisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_admin_can_create_inventaris()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/inventaris', [
            'nama_barang' => 'Laptop Baru',
            'deskripsi' => 'Laptop admin',
            'stok_sekarang' => 2,
            'satuan' => 'Unit'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('inventaris', ['nama_barang' => 'Laptop Baru']);
    }

    public function test_admin_can_record_mutasi()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $inventaris = Inventaris::factory()->create(['stok_sekarang' => 10]);

        $response = $this->actingAs($admin, 'sanctum')->postJson("/api/inventaris/{$inventaris->id}/mutasi", [
            'tipe' => 'masuk',
            'jumlah' => 5,
            'keterangan' => 'Tambahan laptop', 'tanggal_mutasi' => now()->toDateString()
        ]);

        $response->assertStatus(201);
        $this->assertEquals(15, $inventaris->fresh()->stok_sekarang);
    }
}
