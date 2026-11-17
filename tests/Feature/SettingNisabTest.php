<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingNisabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'donatur', 'guard_name' => 'web']);
    }

    public function test_public_can_get_nisab_zakat_defaults()
    {
        $response = $this->getJson('/api/landing/nisab-zakat');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.haul_kondisi_bulan', 12)
            ->assertJsonPath('data.tarif_persen', 2.5)
            ->assertJsonPath('data.nisab_bulanan', 7640144)
            ->assertJsonPath('data.nisab_tahunan', 91681728);
    }

    public function test_admin_nisab_endpoint_requires_proper_role()
    {
        $this->getJson('/api/settings/nisab-zakat')->assertStatus(401);

        $donor = User::factory()->create();
        $donor->assignRole('donatur');
        $this->actingAs($donor, 'sanctum')
            ->getJson('/api/settings/nisab-zakat')
            ->assertStatus(403);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/settings/nisab-zakat')
            ->assertStatus(200)
            ->assertJsonPath('data.haul_kondisi_bulan', 12);
    }

    public function test_only_super_admin_can_update_nisab_zakat()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $payload = [
            'tahun' => 2026,
            'dasar_hukum' => 'SK Ketua BAZNAS No. 15 Tahun 2026 Revisi',
            'nisab_tahunan' => 95000000,
            'nisab_bulanan' => 7916667,
            'tarif_persen' => 2.5,
            'harga_emas_acuan_per_gram' => 1117647,
            'haul_kondisi_bulan' => 12,
            'keterangan' => 'Penyesuaian semester',
        ];

        $this->postJson('/api/settings/nisab-zakat', $payload)->assertStatus(401);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/settings/nisab-zakat', $payload)
            ->assertStatus(403);

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->postJson('/api/settings/nisab-zakat', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.haul_kondisi_bulan', 12)
            ->assertJsonPath('data.nisab_tahunan', 95000000);

        $this->assertDatabaseHas('settings', [
            'key' => 'nisab_zakat_baznas',
        ]);

        $publicResponse = $this->getJson('/api/landing/nisab-zakat');
        $publicResponse->assertStatus(200)
            ->assertJsonPath('data.dasar_hukum', 'SK Ketua BAZNAS No. 15 Tahun 2026 Revisi')
            ->assertJsonPath('data.haul_kondisi_bulan', 12);
    }

    public function test_update_nisab_validates_constraints()
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $invalidPayload = [
            'tahun' => 2019,
            'dasar_hukum' => '',
            'nisab_tahunan' => 0,
            'nisab_bulanan' => -100,
            'tarif_persen' => 150,
            'haul_kondisi_bulan' => 0,
        ];

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->postJson('/api/settings/nisab-zakat', $invalidPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'tahun',
                'dasar_hukum',
                'nisab_tahunan',
                'nisab_bulanan',
                'tarif_persen',
                'haul_kondisi_bulan',
            ]);
    }
}
