<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

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
            'nama_kategori' => 'Pendidikan',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('kategori_artikels', ['nama_kategori' => 'Pendidikan']);
    }
}
