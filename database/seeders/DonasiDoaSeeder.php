<?php

namespace Database\Seeders;

use App\Models\Donasi;
use App\Models\Kampanye;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DonasiDoaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kampanye = Kampanye::first();
        $kampanyeId = $kampanye ? $kampanye->id : null;

        $doaDonaturs = [
            [
                'nama' => 'Bambang Triyono',
                'pesan' => 'Semoga YAMUTI semakin berkah dan amanah. Semoga sedikit dari kami bisa bermanfaat untuk adik-adik santri panti.',
                'nominal' => 250000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Rini Wahyuni',
                'pesan' => 'Bismillah, titip doa untuk almarhum ayah saya. Semoga sedekah ini menjadi amal jariyah penuntun ke surga baginya.',
                'nominal' => 500000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Dwi Prasetyo',
                'pesan' => 'Semoga anak-anak panti selalu diberi kesehatan, kelancaran hafalan Qur\'an, dan tumbuh menjadi generasi sholeh.',
                'nominal' => 150000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Siti Maryam',
                'pesan' => 'Alhamdulillah bisa ikut berbagi bulan ini. Semoga seluruh pengurus YAMUTI senantiasa sehat dan selalu istiqomah dalam dakwah.',
                'nominal' => 300000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Putri Ayu',
                'pesan' => 'Semoga sedekah ini membersihkan rezeki dan membawa berkah melimpah. Sukses terus untuk program-program YAMUTI.',
                'nominal' => 200000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Rahmat Hidayat',
                'pesan' => 'Titip doa agar keluarga kami diberi kelancaran rezeki yang halal dan keberkahan usia. Aamiin ya Rabbal alamin.',
                'nominal' => 350000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Nurul Fatimah',
                'pesan' => 'Masyaallah, bahagia sekali melihat perkembangan asrama panti. Semoga sarana belajarnya cepat tuntas dan membawa berkah.',
                'nominal' => 1000000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Yusuf Sulaeman',
                'pesan' => 'Semoga bermanfaat untuk biaya SPP dan seragam adik-adik. Jangan patah semangat belajarnya ya nak, kalian calon pemimpin bangsa!',
                'nominal' => 400000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Agus Kurniawan',
                'pesan' => 'Untuk semua ustadz dan pengurus YAMUTI, semoga Allah SWT membalas segala keikhlasan antum semua dengan pahala berlipat ganda.',
                'nominal' => 250000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Hendra Zulkarnain',
                'pesan' => 'Bismillah, mohon doa dari adik-adik santri agar usaha dagang kami dilancarkan dan dimudahkan dalam segala urusan.',
                'nominal' => 500000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Eka Wulandari',
                'pesan' => 'Sedekah jumat berkah untuk anak yatim panti. Semoga jadi penerang kubur orang tua kami tercinta.',
                'nominal' => 100000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Fajar Nugraha',
                'pesan' => 'Semoga adik-adik binaan YAMUTI selalu sehat, ceria, dan terus berprestasi di sekolah maupun asrama hafalan Qur\'an.',
                'nominal' => 200000,
                'is_anonymous' => false,
            ],
            [
                'nama' => 'Hamba Allah',
                'pesan' => 'Doakan agar niat dan hajat kami sekeluarga diijabah oleh Allah Ta\'ala. Bismillah istiqomah.',
                'nominal' => 750000,
                'is_anonymous' => true,
            ],
            [
                'nama' => 'Hamba Allah',
                'pesan' => 'Semoga sedikit rezeki ini bisa menambah keceriaan dan gizi adik-adik santri yatim Tasikmalaya.',
                'nominal' => 300000,
                'is_anonymous' => true,
            ],
        ];

        foreach ($doaDonaturs as $index => $item) {
            Donasi::create([
                'id' => (string) Str::uuid(),
                'user_id' => null, // Guest/Tanpa Akun (sesuai permintaan user)
                'kampanye_id' => $kampanyeId,
                'nama_donatur' => $item['nama'],
                'no_whatsapp' => '08' . rand(1111111111, 9999999999),
                'pesan' => $item['pesan'],
                'gross_amount' => $item['nominal'],
                'status' => 'PAID',
                'payment_type' => 'qris',
                'transaction_id' => 'TRX-DOA-' . strtoupper(Str::random(10)),
                'is_anonymous' => $item['is_anonymous'],
                'created_at' => now()->subDays(rand(1, 30))->subHours(rand(1, 23)),
            ]);
        }
    }
}
