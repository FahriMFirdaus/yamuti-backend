<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'api']);
    }

    public function test_owner_can_create_admin()
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/admins', [
            'name' => 'New Admin',
            'email' => 'newadmin@yamuti.org',
            'password' => 'password123',
            'no_whatsapp' => '08123456789',
            'role' => 'admin'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'newadmin@yamuti.org']);
    }
}
