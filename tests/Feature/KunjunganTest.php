<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Kunjungan;
use Spatie\Permission\Models\Role;

class KunjunganTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    }

    public function test_public_can_create_kunjungan()
    {
        $response = $this->postJson('/api/kunjungan', [
            'nama_tamu' => 'Pak Budi',
            'instansi' => 'PT Makmur',
            'no_whatsapp' => '081222333444',
            'tanggal_rencana' => '2026-10-10',
            'tujuan' => 'Donasi dan silaturahmi',
            'jumlah_peserta' => 5
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('kunjungans', ['nama_tamu' => 'Pak Budi', 'status' => 'PENDING']);
    }

    public function test_admin_can_approve_kunjungan()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $kunjungan = Kunjungan::factory()->create(['status' => 'PENDING']);

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/kunjungan/{$kunjungan->id}/status", [
            'status' => 'APPROVED'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('APPROVED', $kunjungan->fresh()->status);
    }
}
