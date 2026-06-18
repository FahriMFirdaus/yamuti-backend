<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class GaleriTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_admin_can_upload_galeri()
    {
        Storage::fake('public');
        
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $file = UploadedFile::fake()->image('foto.jpg');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/galeri', [
            'judul' => 'Foto Kegiatan',
            'deskripsi' => 'Kegiatan baksos',
            'file' => $file
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('galeris', ['judul' => 'Foto Kegiatan']);
    }
}
