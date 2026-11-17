<?php

namespace Database\Seeders;

use App\Models\Kampanye;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Inisialisasi Role Dasar
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $donaturRole = Role::firstOrCreate(['name' => 'donatur', 'guard_name' => 'web']);
        $guestRole = Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'web']);

        // 2. Buat Akun Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@yamuti.org'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('password'),
            ]
        );
        $superAdmin->assignRole($superAdminRole);

        // Buat Akun Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@yamuti.org'],
            [
                'name' => 'Admin System',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole($adminRole);

        // 3. Generate Data Palsu (Dummy) untuk Keperluan Frontend
        $this->call([
            AnakAsuhSeeder::class,
            KampanyeSeeder::class,
            InventarisSeeder::class,
            DonasiDoaSeeder::class,
            GaleriSeeder::class,
        ]);

        Kampanye::firstOrCreate(
            ['slug' => 'zakat-maal'],
            [
                'id' => (string) Str::uuid(),
                'judul' => 'Zakat Maal',
                'deskripsi' => 'Penyaluran zakat harta simpanan dan tabungan sesuai nisab 85 gram emas BAZNAS RI.',
                'target_donasi' => 1000000000,
                'status' => 'Aktif',
                'thumbnail' => 'https://images.unsplash.com/photo-1607613009820-a29f7bb81c04?q=80&w=800&auto=format&fit=crop',
                'tanggal_mulai' => now()->startOfYear()->format('Y-m-d'),
                'tanggal_berakhir' => now()->addYears(2)->format('Y-m-d'),
            ]
        );

        Kampanye::firstOrCreate(
            ['slug' => 'zakat-profesi'],
            [
                'id' => (string) Str::uuid(),
                'judul' => 'Zakat Profesi',
                'deskripsi' => 'Penyaluran zakat penghasilan dan profesi sesuai standar SK Ketua BAZNAS RI.',
                'target_donasi' => 500000000,
                'status' => 'Aktif',
                'thumbnail' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?q=80&w=800&auto=format&fit=crop',
                'tanggal_mulai' => now()->startOfYear()->format('Y-m-d'),
                'tanggal_berakhir' => now()->addYears(2)->format('Y-m-d'),
            ]
        );
    }
}
