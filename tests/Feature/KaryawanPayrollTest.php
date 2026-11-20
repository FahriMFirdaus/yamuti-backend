<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JurnalItem;
use App\Models\JurnalUmum;
use App\Models\Karyawan;
use App\Models\Payroll;
use App\Models\TransaksiKeuangan;
use App\Models\User;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KaryawanPayrollTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'super_admin']);
        $this->seed(ChartOfAccountSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_guest_or_unauthorized_cannot_access_karyawan_or_payroll()
    {
        $regularUser = User::factory()->create();

        // Guest access
        $this->getJson('/api/karyawan')->assertStatus(401);
        $this->getJson('/api/payroll')->assertStatus(401);

        // Unauthorized regular user without admin role
        $this->actingAs($regularUser, 'sanctum')->getJson('/api/karyawan')->assertStatus(403);
        $this->actingAs($regularUser, 'sanctum')->getJson('/api/payroll')->assertStatus(403);
    }

    public function test_admin_can_crud_karyawan()
    {
        // 1. Create Karyawan
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/karyawan', [
            'nama_lengkap' => 'Ustadz Ahmad Fauzi',
            'nik' => '3278010101900001',
            'jenis_kelamin' => 'Laki-laki',
            'tipe_sdm' => 'Pengasuh/Ustadz',
            'jabatan' => 'Pengasuh Asrama Putra & Tahfidz',
            'divisi' => 'Kepengasuhan & Asrama',
            'status_kepegawaian' => 'Tetap',
            'status_aktif' => 'Aktif',
            'gaji_pokok_standar' => 3000000,
            'tunjangan_tetap_standar' => 500000,
            'nama_bank' => 'Bank Syariah Indonesia (BSI)',
            'nomor_rekening' => '7123456789',
            'atas_nama_rekening' => 'Ahmad Fauzi',
        ]);

        $response->assertStatus(201);
        $karyawanId = $response->json('data.id');
        $nip = $response->json('data.nip');

        $this->assertNotEmpty($nip);
        $this->assertStringStartsWith('YMT-', $nip);
        $this->assertDatabaseHas('karyawans', [
            'id' => $karyawanId,
            'nama_lengkap' => 'Ustadz Ahmad Fauzi',
            'tipe_sdm' => 'Pengasuh/Ustadz',
        ]);

        // 2. Read Detail
        $showRes = $this->actingAs($this->admin, 'sanctum')->getJson("/api/karyawan/{$karyawanId}");
        $showRes->assertStatus(200);
        $this->assertEquals('Ustadz Ahmad Fauzi', $showRes->json('data.nama_lengkap'));

        // 3. Update Karyawan
        $updateRes = $this->actingAs($this->admin, 'sanctum')->putJson("/api/karyawan/{$karyawanId}", [
            'nama_lengkap' => 'Ustadz Ahmad Fauzi, S.Pd.I',
            'gaji_pokok_standar' => 3200000,
        ]);
        $updateRes->assertStatus(200);
        $this->assertDatabaseHas('karyawans', [
            'id' => $karyawanId,
            'nama_lengkap' => 'Ustadz Ahmad Fauzi, S.Pd.I',
            'gaji_pokok_standar' => 3200000,
        ]);

        // 4. Summary & Filter
        $summaryRes = $this->actingAs($this->admin, 'sanctum')->getJson('/api/karyawan/summary');
        $summaryRes->assertStatus(200);
        $this->assertEquals(1, $summaryRes->json('data.total'));
        $this->assertEquals(1, $summaryRes->json('data.pengasuh'));

        $filterRes = $this->actingAs($this->admin, 'sanctum')->getJson('/api/karyawan?tipe_sdm=Pengasuh/Ustadz');
        $filterRes->assertStatus(200);
        $this->assertCount(1, $filterRes->json('data.data'));

        // 5. Delete (Soft delete)
        $deleteRes = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/karyawan/{$karyawanId}");
        $deleteRes->assertStatus(200);
        $this->assertSoftDeleted('karyawans', ['id' => $karyawanId]);
    }

    public function test_admin_can_generate_bulk_payroll_for_active_karyawan()
    {
        // Active Pengasuh
        $pengasuh = Karyawan::create([
            'nip' => 'YMT-2026-0001',
            'nama_lengkap' => 'Ustadz Ridwan',
            'jenis_kelamin' => 'Laki-laki',
            'tipe_sdm' => 'Pengasuh/Ustadz',
            'jabatan' => 'Ustadz Tahfidz',
            'status_aktif' => 'Aktif',
            'gaji_pokok_standar' => 3000000,
            'tunjangan_tetap_standar' => 500000,
        ]);

        // Active Staf
        $staf = Karyawan::create([
            'nip' => 'YMT-2026-0002',
            'nama_lengkap' => 'Siti Fatimah',
            'jenis_kelamin' => 'Perempuan',
            'tipe_sdm' => 'Staf/Amil',
            'jabatan' => 'Staf Administrasi',
            'status_aktif' => 'Aktif',
            'gaji_pokok_standar' => 2800000,
            'tunjangan_tetap_standar' => 300000,
        ]);

        // Inactive Staff (should be skipped)
        Karyawan::create([
            'nip' => 'YMT-2026-0003',
            'nama_lengkap' => 'Budi Santoso',
            'jenis_kelamin' => 'Laki-laki',
            'tipe_sdm' => 'Staf/Amil',
            'jabatan' => 'Mantan Driver',
            'status_aktif' => 'Nonaktif',
            'gaji_pokok_standar' => 2000000,
        ]);

        // Generate bulk for November 2026
        $res = $this->actingAs($this->admin, 'sanctum')->postJson('/api/payroll/generate-bulk', [
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
        ]);

        $res->assertStatus(201);
        $this->assertEquals(2, $res->json('data.generated_count'));
        $this->assertDatabaseCount('payrolls', 2);

        // Attempting to re-generate for the same period generates 0 drafts (no duplicates)
        $reGenRes = $this->actingAs($this->admin, 'sanctum')->postJson('/api/payroll/generate-periode', [
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
        ]);
        $reGenRes->assertStatus(200);
        $this->assertEquals(0, $reGenRes->json('data.generated_count'));
    }

    public function test_payroll_state_transitions_and_approval()
    {
        $karyawan = Karyawan::create([
            'nip' => 'YMT-2026-0010',
            'nama_lengkap' => 'Ustadzah Maryam',
            'jenis_kelamin' => 'Perempuan',
            'tipe_sdm' => 'Pengasuh/Ustadz',
            'jabatan' => 'Pengasuh Putri',
            'status_aktif' => 'Aktif',
            'gaji_pokok_standar' => 3000000,
        ]);

        $createRes = $this->actingAs($this->admin, 'sanctum')->postJson('/api/payroll', [
            'karyawan_id' => $karyawan->id,
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'gaji_pokok' => 3000000,
            'tunjangan_jabatan' => 500000,
            'bonus_tambahan' => 200000,
            'potongan_kasbon' => 100000,
        ]);

        $createRes->assertStatus(201);
        $payrollId = $createRes->json('data.id');
        $this->assertEquals(3600000, $createRes->json('data.gaji_bersih'));
        $this->assertEquals('Draft', $createRes->json('data.status'));

        // Approve transition (Draft -> Disetujui)
        $approveRes = $this->actingAs($this->admin, 'sanctum')->postJson("/api/payroll/{$payrollId}/approve");
        $approveRes->assertStatus(200);
        $this->assertEquals('Disetujui', $approveRes->json('data.status'));
        $this->assertEquals($this->admin->id, $approveRes->json('data.disetujui_oleh'));
    }

    public function test_isak35_journal_balance_and_classification_for_pengasuh_and_staf()
    {
        // 1. Pengasuh test -> Debit 5-1007, Credit Kas/Bank (1-1003 BSI)
        $pengasuh = Karyawan::create([
            'nip' => 'YMT-2026-0021',
            'nama_lengkap' => 'Ustadz Hasan',
            'jenis_kelamin' => 'Laki-laki',
            'tipe_sdm' => 'Pengasuh/Ustadz',
            'jabatan' => 'Kepala Asrama Putra',
            'status_aktif' => 'Aktif',
        ]);

        $payrollPengasuh = Payroll::create([
            'nomor_slip' => 'SLIP-202611-0021',
            'karyawan_id' => $pengasuh->id,
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'gaji_pokok' => 3500000,
            'total_pendapatan' => 3500000,
            'total_potongan' => 0,
            'gaji_bersih' => 3500000,
            'status' => 'Disetujui',
        ]);

        $bsiAccount = ChartOfAccount::where('kode_akun', '1-1003')->firstOrFail();

        $payPengasuhRes = $this->actingAs($this->admin, 'sanctum')->postJson("/api/payroll/{$payrollPengasuh->id}/pay", [
            'metode_pembayaran' => 'Transfer Bank',
            'kas_bank_akun_id' => $bsiAccount->id,
            'tanggal_pembayaran' => '2026-11-25',
        ]);

        $payPengasuhRes->assertStatus(200);
        $this->assertEquals('Dibayar', $payPengasuhRes->json('data.status'));

        $payrollPengasuh->refresh();
        $this->assertNotNull($payrollPengasuh->jurnal_umum_id);

        $jurnalPengasuh = JurnalUmum::with('items.account')->findOrFail($payrollPengasuh->jurnal_umum_id);
        $this->assertEquals('Payroll', $jurnalPengasuh->sumber_transaksi);
        $this->assertEquals($payrollPengasuh->id, $jurnalPengasuh->referensi_id);

        // Verify Debit is 5-1007 and Credit is 1-1003
        $itemsPengasuh = $jurnalPengasuh->items;
        $this->assertCount(2, $itemsPengasuh);

        $debitItem = $itemsPengasuh->firstWhere('debit', '>', 0);
        $kreditItem = $itemsPengasuh->firstWhere('kredit', '>', 0);

        $this->assertNotNull($debitItem);
        $this->assertNotNull($kreditItem);
        $this->assertEquals('5-1007', $debitItem->account->kode_akun);
        $this->assertEquals(3500000, (float) $debitItem->debit);
        $this->assertEquals('1-1003', $kreditItem->account->kode_akun);
        $this->assertEquals(3500000, (float) $kreditItem->kredit);
        $this->assertEquals((float) $debitItem->debit, (float) $kreditItem->kredit);

        // Verify operational cash record in transaksi_keuangans
        $this->assertDatabaseHas('transaksi_keuangans', [
            'tipe_transaksi' => 'Kredit',
            'nominal' => 3500000,
            'kategori_pengeluaran' => 'Beban Honor & Gaji',
        ]);

        // 2. Staf/Amil test -> Debit 5-1008, Credit Kas Tunai (1-1001)
        $staf = Karyawan::create([
            'nip' => 'YMT-2026-0022',
            'nama_lengkap' => 'Asep Gunawan',
            'jenis_kelamin' => 'Laki-laki',
            'tipe_sdm' => 'Staf/Amil',
            'jabatan' => 'Staf Logistik & Driver',
            'status_aktif' => 'Aktif',
        ]);

        $payrollStaf = Payroll::create([
            'nomor_slip' => 'SLIP-202611-0022',
            'karyawan_id' => $staf->id,
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'gaji_pokok' => 2500000,
            'total_pendapatan' => 2500000,
            'total_potongan' => 0,
            'gaji_bersih' => 2500000,
            'status' => 'Disetujui',
        ]);

        $kasTunaiAccount = ChartOfAccount::where('kode_akun', '1-1001')->firstOrFail();

        $payStafRes = $this->actingAs($this->admin, 'sanctum')->postJson("/api/payroll/{$payrollStaf->id}/pay", [
            'metode_pembayaran' => 'Kas Tunai',
            'kas_bank_akun_id' => $kasTunaiAccount->id,
            'tanggal_pembayaran' => '2026-11-25',
        ]);

        $payStafRes->assertStatus(200);
        $payrollStaf->refresh();
        $jurnalStaf = JurnalUmum::with('items.account')->findOrFail($payrollStaf->jurnal_umum_id);

        $itemsStaf = $jurnalStaf->items;
        $debitStaf = $itemsStaf->firstWhere('debit', '>', 0);
        $kreditStaf = $itemsStaf->firstWhere('kredit', '>', 0);

        $this->assertEquals('5-1008', $debitStaf->account->kode_akun);
        $this->assertEquals(2500000, (float) $debitStaf->debit);
        $this->assertEquals('1-1001', $kreditStaf->account->kode_akun);
        $this->assertEquals(2500000, (float) $kreditStaf->kredit);
        $this->assertEquals((float) $debitStaf->debit, (float) $kreditStaf->kredit);
    }

    public function test_re_pay_attempt_rejected_with_422()
    {
        $karyawan = Karyawan::create([
            'nip' => 'YMT-2026-0030',
            'nama_lengkap' => 'Ustadz Yusuf',
            'jenis_kelamin' => 'Laki-laki',
            'tipe_sdm' => 'Pengasuh/Ustadz',
            'jabatan' => 'Ustadz',
            'status_aktif' => 'Aktif',
        ]);

        $payroll = Payroll::create([
            'nomor_slip' => 'SLIP-202611-0030',
            'karyawan_id' => $karyawan->id,
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'gaji_pokok' => 3000000,
            'total_pendapatan' => 3000000,
            'total_potongan' => 0,
            'gaji_bersih' => 3000000,
            'status' => 'Disetujui',
        ]);

        // First payment succeeds
        $payRes1 = $this->actingAs($this->admin, 'sanctum')->postJson("/api/payroll/{$payroll->id}/pay");
        $payRes1->assertStatus(200);

        $initialJournalCount = JurnalUmum::count();

        // Second payment attempt MUST be rejected with HTTP 422
        $payRes2 = $this->actingAs($this->admin, 'sanctum')->postJson("/api/payroll/{$payroll->id}/pay");
        $payRes2->assertStatus(422);
        $this->assertStringContainsString('sudah berstatus Dibayar', $payRes2->json('message'));

        // Assert no duplicate journal created
        $this->assertEquals($initialJournalCount, JurnalUmum::count());
    }

    public function test_editing_or_deleting_paid_payroll_rejected_with_422()
    {
        $karyawan = Karyawan::create([
            'nip' => 'YMT-2026-0040',
            'nama_lengkap' => 'Dedi Iskandar',
            'jenis_kelamin' => 'Laki-laki',
            'tipe_sdm' => 'Staf/Amil',
            'jabatan' => 'Security',
            'status_aktif' => 'Aktif',
        ]);

        $payroll = Payroll::create([
            'nomor_slip' => 'SLIP-202611-0040',
            'karyawan_id' => $karyawan->id,
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'gaji_pokok' => 2000000,
            'total_pendapatan' => 2000000,
            'total_potongan' => 0,
            'gaji_bersih' => 2000000,
            'status' => 'Draft',
        ]);

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/payroll/{$payroll->id}/pay")->assertStatus(200);

        // Edit attempt rejected
        $updateRes = $this->actingAs($this->admin, 'sanctum')->putJson("/api/payroll/{$payroll->id}", [
            'gaji_pokok' => 2500000,
        ]);
        $updateRes->assertStatus(422);
        $this->assertStringContainsString('tidak dapat diubah', $updateRes->json('message'));

        // Delete attempt rejected
        $deleteRes = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/payroll/{$payroll->id}");
        $deleteRes->assertStatus(422);
        $this->assertStringContainsString('tidak dapat dihapus', $deleteRes->json('message'));
    }

    public function test_slip_endpoint_returns_correct_terbilang_and_breakdown()
    {
        $karyawan = Karyawan::create([
            'nip' => 'YMT-2026-0050',
            'nama_lengkap' => 'Ustadz Abdullah',
            'nik' => '3278010101900050',
            'jenis_kelamin' => 'Laki-laki',
            'tipe_sdm' => 'Pengasuh/Ustadz',
            'jabatan' => 'Ustadz Senior',
            'divisi' => 'Pendidikan',
            'nama_bank' => 'BSI',
            'nomor_rekening' => '7112233445',
            'atas_nama_rekening' => 'Abdullah',
            'status_aktif' => 'Aktif',
        ]);

        $payroll = Payroll::create([
            'nomor_slip' => 'SLIP-202611-0050',
            'karyawan_id' => $karyawan->id,
            'periode_bulan' => 11,
            'periode_tahun' => 2026,
            'gaji_pokok' => 3000000,
            'tunjangan_jabatan' => 500000,
            'tunjangan_kehadiran' => 200000,
            'bonus_tambahan' => 100000,
            'total_pendapatan' => 3800000,
            'potongan_kasbon' => 300000,
            'total_potongan' => 300000,
            'gaji_bersih' => 3500000,
            'status' => 'Disetujui',
        ]);

        $this->actingAs($this->admin, 'sanctum')->postJson("/api/payroll/{$payroll->id}/pay");

        $slipRes = $this->actingAs($this->admin, 'sanctum')->getJson("/api/payroll/{$payroll->id}/slip");
        $slipRes->assertStatus(200);

        $data = $slipRes->json('data');
        $this->assertEquals('SLIP-202611-0050', $data['slip']['nomor_slip']);
        $this->assertEquals('November 2026', $data['slip']['nama_periode']);
        $this->assertEquals('Ustadz Abdullah', $data['karyawan']['nama_lengkap']);
        $this->assertEquals(3800000, $data['pendapatan']['total_pendapatan']);
        $this->assertEquals(300000, $data['potongan']['total_potongan']);
        $this->assertEquals(3500000, $data['gaji_bersih']);
        $this->assertEquals('Tiga Juta Lima Ratus Ribu Rupiah', $data['terbilang']);
        $this->assertNotNull($data['yayasan']['nama']);
    }
}
