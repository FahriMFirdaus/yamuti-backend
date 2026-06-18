<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\KategoriArtikel;
use Spatie\Permission\Models\Role;

class KategoriArtikelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_admin_can_create_kategori()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/kategori-artikel', [
            'nama' => 'Pendidikan'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('kategori_artikels', ['nama' => 'Pendidikan']);
    }
}
