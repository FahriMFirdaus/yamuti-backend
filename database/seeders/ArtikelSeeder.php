<?php

namespace Database\Seeders;

use App\Models\Artikel;
use App\Models\KategoriArtikel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArtikelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first() ?? User::create([
            'name' => 'Admin YAMUTI',
            'email' => 'admin@yamuti.org',
            'password' => bcrypt('password'),
        ]);

        // Kategori Artikel
        $kategoriKegiatan = KategoriArtikel::firstOrCreate(
            ['slug' => 'kegiatan-panti'],
            ['nama_kategori' => 'Kegiatan Panti']
        );

        $kategoriPenyaluran = KategoriArtikel::firstOrCreate(
            ['slug' => 'penyaluran-donasi'],
            ['nama_kategori' => 'Penyaluran']
        );

        $kategoriKabar = KategoriArtikel::firstOrCreate(
            ['slug' => 'kabar-santri'],
            ['nama_kategori' => 'Kabar Santri']
        );

        // Data Artikel Nyata YAMUTI
        $artikels = [
            [
                'judul' => 'Keseruan Buka Puasa Bersama & Santunan 100 Anak Yatim Piatu',
                'konten' => 'Alhamdulillah, puji syukur ke hadirat Allah SWT, kegiatan buka puasa bersama dan santunan paket perlengkapan ibadah untuk adik-adik yatim telah terlaksana dengan penuh berkah dan sukacita di asrama YAMUTI Tasikmalaya. Terima kasih kami haturkan kepada segenap muhsinin dan donatur yang telah mengalirkan kebaikan.',
                'kategori_id' => $kategoriKegiatan->id,
                'thumbnail_url' => 'https://images.unsplash.com/photo-1593113598332-cd288d649433?q=80&w=800&auto=format&fit=crop',
                'created_at' => now()->subDays(3),
            ],
            [
                'judul' => 'Penyaluran Beasiswa Pendidikan & Perlengkapan Belajar Tahap 1',
                'konten' => 'Sebanyak 35 anak asuh binaan YAMUTI telah menerima paket beasiswa pendidikan dan seragam sekolah baru untuk semester ajaran ini. Bantuan ini diserahkan langsung oleh perwakilan pengurus didampingi para relawan.',
                'kategori_id' => $kategoriPenyaluran->id,
                'thumbnail_url' => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=800&auto=format&fit=crop',
                'created_at' => now()->subDays(7),
            ],
            [
                'judul' => 'Pemeriksaan Kesehatan Gigi dan Mata Berkala untuk Santri Tahfidz',
                'konten' => 'Kesehatan para santri merupakan prioritas utama kami. Bekerja sama dengan tim medis dan dokter sukarelawan, YAMUTI mengadakan pemeriksaan kesehatan gratis meliputi kesehatan gigi, mata, serta pembagian vitamin daya tahan tubuh.',
                'kategori_id' => $kategoriKabar->id,
                'thumbnail_url' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?q=80&w=800&auto=format&fit=crop',
                'created_at' => now()->subDays(12),
            ],
            [
                'judul' => 'Kunjungan Silaturahmi & Kelas Inspirasi Bersama Komunitas Sahabat Berbagi',
                'konten' => 'Suasana hangat dan penuh keceriaan menyelimuti asrama saat Komunitas Sahabat Berbagi hadir memberikan kelas motivasi, mendongeng kisah teladan nabi, dan bermain edukatif bersama adik-adik panti asuhan.',
                'kategori_id' => $kategoriKegiatan->id,
                'thumbnail_url' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?q=80&w=800&auto=format&fit=crop',
                'created_at' => now()->subDays(18),
            ],
        ];

        foreach ($artikels as $art) {
            Artikel::updateOrCreate(
                ['slug' => Str::slug($art['judul'])],
                [
                    'id' => (string) Str::uuid(),
                    'judul' => $art['judul'],
                    'konten' => $art['konten'],
                    'kategori_id' => $art['kategori_id'],
                    'thumbnail_url' => $art['thumbnail_url'],
                    'penulis_id' => $admin->id,
                    'created_at' => $art['created_at'],
                    'updated_at' => $art['created_at'],
                ]
            );
        }
    }
}
