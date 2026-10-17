<?php

namespace Database\Seeders;

use App\Models\Kampanye;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KampanyeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Program Nyata & Representatif YAMUTI Tasikmalaya
        $programs = [
            [
                'judul' => 'Sedekah Beras & Pangan Santri Yatim Piatu',
                'deskripsi' => 'Program pemenuhan nutrisi dan beras bulanan bagi 45+ anak asuh dan santri yatim dhuafa di asrama YAMUTI Tasikmalaya. Memastikan adik-adik santri dapat beribadah dan belajar menghafal Al-Qur\'an dengan asupan gizi yang layak.',
                'target_donasi' => 15000000,
                'status' => 'Mendesak',
                'thumbnail' => 'https://images.unsplash.com/photo-1593113598332-cd288d649433?q=80&w=800&auto=format&fit=crop',
                'tanggal_mulai' => now()->startOfMonth()->format('Y-m-d'),
                'tanggal_berakhir' => now()->addMonths(3)->format('Y-m-d'),
            ],
            [
                'judul' => 'Beasiswa Pendidikan & Seragam Sekolah Anak Dhuafa',
                'deskripsi' => 'Bantuan SPP bulanan, alat tulis, seragam, dan buku pelajaran untuk anak-anak asuh usia SD hingga SMA binaan YAMUTI agar tidak putus sekolah dan menggapai cita-cita mereka.',
                'target_donasi' => 25000000,
                'status' => 'Aktif',
                'thumbnail' => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?q=80&w=800&auto=format&fit=crop',
                'tanggal_mulai' => now()->startOfMonth()->format('Y-m-d'),
                'tanggal_berakhir' => now()->addMonths(6)->format('Y-m-d'),
            ],
            [
                'judul' => 'Renovasi Asrama & Sarana Belajar YAMUTI Tasikmalaya',
                'deskripsi' => 'Pengadaan ranjang bertingkat, perbaikan atap bocor, dan penataan ruang belajar asrama putra dan putri agar santri yatim dapat beristirahat dan belajar dengan aman dan nyaman.',
                'target_donasi' => 45000000,
                'status' => 'Aktif',
                'thumbnail' => 'https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?q=80&w=800&auto=format&fit=crop',
                'tanggal_mulai' => now()->startOfMonth()->format('Y-m-d'),
                'tanggal_berakhir' => now()->addMonths(5)->format('Y-m-d'),
            ],
            [
                'judul' => 'Kesehatan Berkala & Vitamin Santri Penghafal Qur\'an',
                'deskripsi' => 'Layanan pemeriksaan dokter berkala, suplemen vitamin daya tahan tubuh, dan posko kesehatan santri di lingkungan asrama panti asuhan.',
                'target_donasi' => 10000000,
                'status' => 'Aktif',
                'thumbnail' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?q=80&w=800&auto=format&fit=crop',
                'tanggal_mulai' => now()->startOfMonth()->format('Y-m-d'),
                'tanggal_berakhir' => now()->addMonths(4)->format('Y-m-d'),
            ],
            [
                'judul' => 'Kado Bahagia & Santunan Yatim Ceria',
                'deskripsi' => 'Santunan tunai dan bingkisan parcel kebahagiaan untuk membahagiakan hati anak-anak yatim binaan non-panti di pelosok Tasikmalaya.',
                'target_donasi' => 20000000,
                'status' => 'Aktif',
                'thumbnail' => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=800&auto=format&fit=crop',
                'tanggal_mulai' => now()->startOfMonth()->format('Y-m-d'),
                'tanggal_berakhir' => now()->addMonths(2)->format('Y-m-d'),
            ],
        ];

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \App\Models\Keranjang::truncate();
        Kampanye::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        foreach ($programs as $prog) {
            Kampanye::create([
                'id' => (string) Str::uuid(),
                'judul' => $prog['judul'],
                'slug' => Str::slug($prog['judul']),
                'deskripsi' => $prog['deskripsi'],
                'target_donasi' => $prog['target_donasi'],
                'status' => $prog['status'],
                'thumbnail' => $prog['thumbnail'],
                'tanggal_mulai' => $prog['tanggal_mulai'],
                'tanggal_berakhir' => $prog['tanggal_berakhir'],
            ]);
        }
    }
}
