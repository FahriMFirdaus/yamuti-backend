<?php

namespace Tests\Feature;

use App\Models\Kampanye;
use App\Models\Keranjang;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ZakatCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_donation_with_zakat_campaign_preserves_item_name()
    {
        $campaign = Kampanye::create([
            'id' => (string) Str::uuid(),
            'judul' => 'Zakat Maal',
            'slug' => 'zakat-maal',
            'deskripsi' => 'Penyaluran zakat harta',
            'target_donasi' => 1000000000,
            'status' => 'Aktif',
        ]);

        $mockMidtrans = Mockery::mock(MidtransService::class);
        $mockMidtrans->shouldReceive('createTransaction')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return isset($payload['item_details'])
                    && count($payload['item_details']) === 1
                    && $payload['item_details'][0]['name'] === 'Zakat Maal'
                    && $payload['item_details'][0]['price'] === 2500000;
            }))
            ->andReturn([
                'snap_token' => 'mock-snap-token-maal',
                'payment_url' => 'https://mock.midtrans/snap/maal',
            ]);

        $this->app->instance(MidtransService::class, $mockMidtrans);

        $response = $this->postJson('/api/donasi', [
            'nama_donatur' => 'Ahmad Muzakki',
            'no_whatsapp' => '081234567890',
            'gross_amount' => 2500000,
            'kampanye_id' => $campaign->id,
            'pesan' => 'Niat zakat harta simpanan',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.snap_token', 'mock-snap-token-maal');
    }

    public function test_single_donation_with_zakat_note_formats_item_name()
    {
        $mockMidtrans = Mockery::mock(MidtransService::class);
        $mockMidtrans->shouldReceive('createTransaction')
            ->once()
            ->with(Mockery::on(function ($payload) {
                return isset($payload['item_details'])
                    && count($payload['item_details']) === 1
                    && $payload['item_details'][0]['name'] === 'Zakat Profesi'
                    && $payload['item_details'][0]['price'] === 350000;
            }))
            ->andReturn([
                'snap_token' => 'mock-snap-token-profesi',
                'payment_url' => 'https://mock.midtrans/snap/profesi',
            ]);

        $this->app->instance(MidtransService::class, $mockMidtrans);

        $response = $this->postJson('/api/donasi', [
            'nama_donatur' => 'Fatimah',
            'no_whatsapp' => '081298765432',
            'gross_amount' => 350000,
            'kampanye_id' => null,
            'pesan' => '[Zakat Profesi] Penyaluran zakat penghasilan bulan ini',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.snap_token', 'mock-snap-token-profesi');
    }

    public function test_cart_checkout_preserves_zakat_campaign_and_notes()
    {
        $user = User::factory()->create([
            'name' => 'Donatur Teladan',
            'email' => 'donatur@yamuti.org',
            'no_hp' => '081122334455',
        ]);

        $campaign = Kampanye::create([
            'id' => (string) Str::uuid(),
            'judul' => 'Zakat Maal',
            'slug' => 'zakat-maal',
            'deskripsi' => 'Zakat harta BAZNAS',
            'target_donasi' => 500000000,
            'status' => 'Aktif',
        ]);

        $cartMaal = Keranjang::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'kampanye_id' => $campaign->id,
            'nominal' => 2000000,
            'nama_donatur' => $user->name,
            'no_whatsapp' => $user->no_hp,
            'pesan' => 'Zakat tabungan 1 tahun',
            'is_anonymous' => false,
        ]);

        $cartProfesi = Keranjang::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'kampanye_id' => null,
            'nominal' => 500000,
            'nama_donatur' => $user->name,
            'no_whatsapp' => $user->no_hp,
            'pesan' => '[Zakat Profesi] Penyaluran zakat pendapatan',
            'is_anonymous' => false,
        ]);

        $mockMidtrans = Mockery::mock(MidtransService::class);
        $mockMidtrans->shouldReceive('createTransaction')
            ->once()
            ->with(Mockery::on(function ($payload) {
                if (!isset($payload['item_details']) || count($payload['item_details']) !== 2) {
                    return false;
                }
                $names = array_column($payload['item_details'], 'name');
                return in_array('Zakat Maal', $names) && in_array('Zakat Profesi', $names);
            }))
            ->andReturn([
                'snap_token' => 'mock-cart-snap-token',
                'payment_url' => 'https://mock.midtrans/snap/cart',
            ]);

        $this->app->instance(MidtransService::class, $mockMidtrans);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/keranjang/checkout', [
                'keranjang_ids' => [$cartMaal->id, $cartProfesi->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.snap_token', 'mock-cart-snap-token')
            ->assertJsonPath('data.total_amount', 2500000);

        $this->assertDatabaseMissing('keranjangs', ['id' => $cartMaal->id]);
        $this->assertDatabaseMissing('keranjangs', ['id' => $cartProfesi->id]);
    }
}
