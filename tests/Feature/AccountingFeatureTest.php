<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JurnalUmum;
use App\Models\JurnalItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountingFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_can_retrieve_neraca_laporan_aktivitas_and_arus_kas()
    {
        $kas = ChartOfAccount::create([
            'kode_akun' => '1-1001',
            'nama_akun' => 'Kas Operasional',
            'tipe_akun' => 'Aset',
            'saldo_normal' => 'Debit',
            'is_active' => true,
        ]);

        $infaq = ChartOfAccount::create([
            'kode_akun' => '4-1001',
            'nama_akun' => 'Penerimaan Infaq',
            'tipe_akun' => 'Penerimaan',
            'saldo_normal' => 'Kredit',
            'is_active' => true,
        ]);

        $beban = ChartOfAccount::create([
            'kode_akun' => '5-1001',
            'nama_akun' => 'Beban Operasional',
            'tipe_akun' => 'Beban',
            'saldo_normal' => 'Debit',
            'is_active' => true,
        ]);

        $jurnal = JurnalUmum::create([
            'nomor_referensi' => 'JRN-2026-TEST',
            'tanggal_transaksi' => now()->toDateString(),
            'keterangan' => 'Penerimaan Infaq Tunai',
            'sumber_transaksi' => 'Donasi',
            'created_by' => $this->admin->id,
        ]);

        JurnalItem::create([
            'jurnal_umum_id' => $jurnal->id,
            'chart_of_account_id' => $kas->id,
            'debit' => 500000,
            'kredit' => 0,
        ]);

        JurnalItem::create([
            'jurnal_umum_id' => $jurnal->id,
            'chart_of_account_id' => $infaq->id,
            'debit' => 0,
            'kredit' => 500000,
        ]);

        // 1. Test Neraca
        $neracaRes = $this->actingAs($this->admin, 'sanctum')->getJson('/api/akuntansi/neraca');
        $neracaRes->assertStatus(200);
        $neracaRes->assertJsonPath('data.is_balanced', true);
        $neracaRes->assertJsonPath('data.aset.total', 500000);

        // 2. Test Laporan Aktivitas
        $aktivitasRes = $this->actingAs($this->admin, 'sanctum')->getJson('/api/akuntansi/laporan-aktivitas');
        $aktivitasRes->assertStatus(200);
        $aktivitasRes->assertJsonPath('data.penerimaan.total', 500000);
        $aktivitasRes->assertJsonPath('data.surplus_defisit', 500000);

        // 3. Test Arus Kas
        $arusKasRes = $this->actingAs($this->admin, 'sanctum')->getJson('/api/akuntansi/arus-kas');
        $arusKasRes->assertStatus(200);
        $arusKasRes->assertJsonPath('data.saldo_akhir_kas', 500000);
        $arusKasRes->assertJsonPath('data.aktivitas_operasi.net', 500000);
    }
}
