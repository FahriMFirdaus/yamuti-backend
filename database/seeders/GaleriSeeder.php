<?php

namespace Database\Seeders;

use App\Models\Galeri;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GaleriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        $fotos = [
            [
                'judul' => 'Keceriaan Anak-Anak Panti Asuhan YAMUTI',
                'deskripsi' => 'Senyum bahagia adik-adik panti asuhan saat bermain bersama di halaman asrama Tasikmalaya.',
                'file_url' => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'judul' => 'Doa Bersama Santri Penghafal Qur\'an',
                'deskripsi' => 'Momen khidmat doa bersama para santri penghafal Al-Qur\'an untuk para donatur setia YAMUTI.',
                'file_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'judul' => 'Kegiatan Belajar & Bimbingan Belajar Harian',
                'deskripsi' => 'Bimbingan belajar matematika dan bahasa Inggris di ruang belajar asrama.',
                'file_url' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'judul' => 'Kebersamaan Pengurus & Relawan YAMUTI',
                'deskripsi' => 'Tim relawan dan pengurus panti saling bahu-membahu dalam pelayanan anak-anak yatim.',
                'file_url' => 'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'judul' => 'Penyaluran Bantuan Sembako & Paket Gizi',
                'deskripsi' => 'Distribusi bahan makanan pokok dan asupan gizi bernutrisi untuk adik-adik santri binaan.',
                'file_url' => 'https://images.unsplash.com/photo-1593113646773-028c64a8f1b8?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'judul' => 'Kunjungan Silaturahmi Komunitas Donatur',
                'deskripsi' => 'Suasana hangat kunjungan dan santunan dari komunitas donatur Tasikmalaya ke asrama.',
                'file_url' => 'https://images.unsplash.com/photo-1564769625905-50e93615e769?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'judul' => 'Pemeriksaan Kesehatan Medis Berkala',
                'deskripsi' => 'Pemeriksaan tensi, kesehatan gigi, dan pembagian vitamin oleh dokter relawan.',
                'file_url' => 'https://images.unsplash.com/photo-1532938911079-1b06ac7ceec7?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'judul' => 'Pelatihan Komputer & Literasi Digital',
                'deskripsi' => 'Pelatihan dasar komputer dan teknologi informasi untuk santri usia SMP dan SMA.',
                'file_url' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'judul' => 'Suasana Asri Lingkungan Asrama YAMUTI',
                'deskripsi' => 'Pemandangan halaman dan ruang terbuka hijau asrama YAMUTI Tasikmalaya.',
                'file_url' => 'https://images.unsplash.com/photo-1529156069898-49953eb1b5ae?q=80&w=800&auto=format&fit=crop',
            ],
            [
                'judul' => 'Penyaluran Paket Belajar & Perlengkapan Sekolah',
                'deskripsi' => 'Kebahagiaan santri saat menerima tas, buku tulis, dan seragam sekolah baru.',
                'file_url' => 'https://images.unsplash.com/photo-1593113598332-cd288d649433?q=80&w=800&auto=format&fit=crop',
            ],
        ];

        foreach ($fotos as $foto) {
            Galeri::create([
                'id' => (string) Str::uuid(),
                'judul' => $foto['judul'],
                'deskripsi' => $foto['deskripsi'],
                'file_url' => $foto['file_url'],
                'diunggah_oleh' => $adminId,
            ]);
        }
    }
}
