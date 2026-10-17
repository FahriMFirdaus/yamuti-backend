<?php

namespace Database\Factories;

use App\Models\AnakAsuh;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnakAsuhFactory extends Factory
{
    protected $model = AnakAsuh::class;

    public function definition(): array
    {
        $jenisKelamin = fake()->randomElement(['Laki-laki', 'Perempuan']);
        return [
            'id' => fake()->uuid(),
            'nama' => fake()->name($jenisKelamin === 'Laki-laki' ? 'male' : 'female'),
            'nik' => fake()->unique()->numerify('################'),
            'no_kk' => fake()->numerify('################'),
            'no_akte' => fake()->numerify('#####/UM/XXXX/####'),
            'foto_identitas' => null,
            'tempat_lahir' => fake()->city(),
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => fake()->dateTimeBetween('-18 years', '-1 year')->format('Y-m-d'),
            'status' => fake()->randomElement(['Aktif', 'Alumni']),
            'kategori_bayi' => fake()->boolean(15),
            'tanggal_masuk' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'keterangan' => fake()->sentence(),
            'status_keluarga' => fake()->randomElement(['Yatim', 'Piatu', 'Yatim Piatu', 'Dhuafa']),
            'tingkat_pendidikan' => fake()->randomElement(['Belum Sekolah', 'TK/PAUD', 'SD', 'SMP', 'SMA/SMK', 'Kuliah']),
            'nama_sekolah' => fake()->company() . ' School',
            'golongan_darah' => fake()->randomElement(['A', 'B', 'AB', 'O', 'Belum Tahu']),
            'riwayat_penyakit' => fake()->boolean(10) ? fake()->randomElement(['Asma', 'Alergi Dingin', 'Maag']) : null,
            'catatan_khusus' => fake()->boolean(20) ? fake()->sentence() : null,
        ];
    }
}
