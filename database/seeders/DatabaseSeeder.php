<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AnakAsuh;
use App\Models\Inventaris;
use App\Models\Donasi;
use App\Models\Kunjungan;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Inisialisasi Role Dasar
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $adminCabangRole = Role::firstOrCreate(['name' => 'Admin Cabang', 'guard_name' => 'web']);
        $donaturRole = Role::firstOrCreate(['name' => 'Donatur', 'guard_name' => 'web']);

        // 2. Buat Akun Super Administrator
        $admin = User::firstOrCreate(
            ['email' => 'admin@yamuti.org'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('password'), // password default: password
            ]
        );
        $admin->assignRole($superAdminRole);

        // 3. Generate Data Palsu (Dummy) untuk Keperluan Frontend (Di non-aktifkan di production karena faker tidak diinstall)
        // AnakAsuh::factory(50)->create();
        // Inventaris::factory(20)->create();
        // Donasi::factory(100)->create();
        // Kunjungan::factory(30)->create();
    }
}
