<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\AnakAsuh;
use Spatie\Permission\Models\Role;

class AnakAsuhTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    }

    public function test_admin_can_create_anak_asuh()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/anak-asuh', [
            'nama_lengkap' => 'Ahmad Fulan',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '2015-05-10',
            'jenis_kelamin' => 'L',
            'status_yatim_piatu' => 'Yatim',
            'tanggal_masuk' => '2023-01-01'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('anak_asuhs', ['nama_lengkap' => 'Ahmad Fulan']);
    }

    public function test_can_get_list_anak_asuh()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        AnakAsuh::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/anak-asuh');

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data' => ['data']]);
    }
}
