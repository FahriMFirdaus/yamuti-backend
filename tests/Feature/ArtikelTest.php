<?php

namespace Tests\Feature;

use App\Models\KategoriArtikel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArtikelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_admin_can_create_artikel()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $kategori = KategoriArtikel::create(['nama_kategori' => 'Pendidikan', 'slug' => 'pendidikan']);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/artikel', [
            'judul' => 'Artikel Baru',
            'konten' => 'Isi konten artikel',
            'kategori_id' => $kategori->id,
            'status' => 'PUBLISHED',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('artikels', ['judul' => 'Artikel Baru']);
    }
}
