<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@yamuti.org',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'no_whatsapp' => '08123456789'
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token']]);
        
        $this->assertDatabaseHas('users', ['email' => 'test@yamuti.org']);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'login@yamuti.org',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@yamuti.org',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token']]);
    }
}
