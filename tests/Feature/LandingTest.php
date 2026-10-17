<?php

namespace Tests\Feature;

use App\Models\AnakAsuh;
use App\Models\Donasi;
use App\Models\Kampanye;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_impact_statistics()
    {
        $response = $this->getJson('/api/landing/impact');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'jiwaterbantu',
                     'danatersalurkan',
                     'programberjalan',
                     'pengabdian'
                 ]);
    }

    public function test_can_get_foundation_benefits()
    {
        $response = $this->getJson('/api/landing/benefits');

        $response->assertStatus(200)
                 ->assertJsonCount(4)
                 ->assertJsonFragment([
                     'title' => 'Transparansi'
                 ]);
    }

    public function test_can_get_hero_slides()
    {
        $response = $this->getJson('/api/landing/slides');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'src', 'alt']
                 ]);
    }
}
