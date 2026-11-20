<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // 1. ASET (Kas & Bank) - Saldo Normal: Debit
            ['kode_akun' => '1-1001', 'nama_akun' => 'Kas Tunai Yayasan', 'tipe_akun' => 'Aset', 'saldo_normal' => 'Debit', 'deskripsi' => 'Kas tunai operasional harian kantor yayasan'],
            ['kode_akun' => '1-1002', 'nama_akun' => 'Kas Operasional Website & IT', 'tipe_akun' => 'Aset', 'saldo_normal' => 'Debit', 'deskripsi' => 'Alokasi kas pemeliharaan sistem & server'],
            ['kode_akun' => '1-1003', 'nama_akun' => 'Bank BSI (Rekening Donasi)', 'tipe_akun' => 'Aset', 'saldo_normal' => 'Debit', 'deskripsi' => 'Rekening resmi penerimaan donasi digital BSI'],
            ['kode_akun' => '1-1004', 'nama_akun' => 'Kas Cabang & Asrama', 'tipe_akun' => 'Aset', 'saldo_normal' => 'Debit', 'deskripsi' => 'Kas kecil pengelola asrama anak binaan'],

            // 2. KEWAJIBAN (Titipan Amanah) - Saldo Normal: Kredit
            ['kode_akun' => '2-1001', 'nama_akun' => 'Titipan Dana Zakat (Mustahik)', 'tipe_akun' => 'Kewajiban', 'saldo_normal' => 'Kredit', 'deskripsi' => 'Dana zakat tertampung menunggu waktu asnaf'],
            ['kode_akun' => '2-1002', 'nama_akun' => 'Titipan Beasiswa POTA', 'tipe_akun' => 'Kewajiban', 'saldo_normal' => 'Kredit', 'deskripsi' => 'Beasiswa orang tua asuh yang siap disalurkan'],

            // 3. ASET NETO (Modal / Ekuitas Nirlaba) - Saldo Normal: Kredit
            ['kode_akun' => '3-1001', 'nama_akun' => 'Aset Neto Tanpa Pembatasan', 'tipe_akun' => 'Aset Neto', 'saldo_normal' => 'Kredit', 'deskripsi' => 'Akumulasi dana bebas operasional yayasan'],
            ['kode_akun' => '3-1002', 'nama_akun' => 'Aset Neto Terikat Program', 'tipe_akun' => 'Aset Neto', 'saldo_normal' => 'Kredit', 'deskripsi' => 'Akumulasi dana program khusus donatur'],

            // 4. PENERIMAAN / PENDAPATAN - Saldo Normal: Kredit
            ['kode_akun' => '4-1001', 'nama_akun' => 'Penerimaan Infaq & Sedekah Umum', 'tipe_akun' => 'Penerimaan', 'saldo_normal' => 'Kredit', 'deskripsi' => 'Donasi umum dari masyarakat'],
            ['kode_akun' => '4-1002', 'nama_akun' => 'Penerimaan Donasi Kampanye Khusus', 'tipe_akun' => 'Penerimaan', 'saldo_normal' => 'Kredit', 'deskripsi' => 'Donasi terikat untuk program/kampanye spesifik'],
            ['kode_akun' => '4-1003', 'nama_akun' => 'Penerimaan Beasiswa POTA', 'tipe_akun' => 'Penerimaan', 'saldo_normal' => 'Kredit', 'deskripsi' => 'Iuran komitmen bulanan orang tua asuh'],
            ['kode_akun' => '4-1004', 'nama_akun' => 'Penerimaan Zakat Maal & Profesi', 'tipe_akun' => 'Penerimaan', 'saldo_normal' => 'Kredit', 'deskripsi' => 'Penerimaan dana zakat dari muzzaki'],

            // 5. BEBAN & PENGELUARAN - Saldo Normal: Debit
            ['kode_akun' => '5-1001', 'nama_akun' => 'Beban Penyaluran Beasiswa & Pendidikan', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit', 'deskripsi' => 'SPP, perlengkapan sekolah, dan uang saku santri'],
            ['kode_akun' => '5-1002', 'nama_akun' => 'Beban Logistik & Konsumsi Asrama', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit', 'deskripsi' => 'Beras, lauk pauk, susu, dan konsumsi harian santri'],
            ['kode_akun' => '5-1003', 'nama_akun' => 'Beban Operasional Asrama (Listrik/Air/Wifi)', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit', 'deskripsi' => 'Tagihan utilitas gedung panti & asrama'],
            ['kode_akun' => '5-1004', 'nama_akun' => 'Beban Pemeliharaan Sarana & Prasarana', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit', 'deskripsi' => 'Renovasi kecil, perbaikan AC, pompa air, dsb'],
            ['kode_akun' => '5-1005', 'nama_akun' => 'Beban Operasional IT, Server & Website', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit', 'deskripsi' => 'Hosting, domain, lisensi sistem, dan pemeliharaan IT'],
            ['kode_akun' => '5-1006', 'nama_akun' => 'Beban Administrasi & Umum', 'tipe_akun' => 'Beban', 'saldo_normal' => 'Debit', 'deskripsi' => 'ATK, transportasi pengurus, dan keperluan legal'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(
                ['kode_akun' => $acc['kode_akun']],
                array_merge($acc, ['id' => (string) Str::uuid()])
            );
        }
    }
}
