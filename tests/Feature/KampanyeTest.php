<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class KampanyeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_admin_can_create_kampanye()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/kampanye', [
            'judul' => 'Kampanye Ramadhan',
            'target_donasi' => 50000000,
            'deskripsi' => 'Deskripsi kampanye'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('kampanyes', ['judul' => 'Kampanye Ramadhan']);
    }
}
