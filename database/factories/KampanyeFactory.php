<?php

namespace Database\Factories;

use App\Models\Kampanye;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kampanye>
 */
class KampanyeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $judul = fake()->unique()->sentence(fake()->numberBetween(4, 7));
        $tanggalMulai = fake()->dateTimeBetween('-1 month', '+1 month');
        $tanggalBerakhir = fake()->dateTimeBetween($tanggalMulai, '+6 months');

        return [
            'id' => fake()->uuid(),
            'judul' => $judul,
            'slug' => \Illuminate\Support\Str::slug($judul) . '-' . time(),
            'deskripsi' => fake()->paragraphs(3, true),
            'target_donasi' => fake()->randomElement([10000000, 25000000, 50000000, 100000000]),
            'tanggal_mulai' => $tanggalMulai->format('Y-m-d'),
            'tanggal_berakhir' => $tanggalBerakhir->format('Y-m-d'),
            'status' => fake()->randomElement(['Aktif', 'Selesai', 'Draft']),
            'thumbnail' => null, // Biarkan null atau dummy image placeholder
        ];
    }
}
