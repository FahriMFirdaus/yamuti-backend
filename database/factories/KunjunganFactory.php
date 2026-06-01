<?php

namespace Database\Factories;

use App\Models\Kunjungan;
use Illuminate\Database\Eloquent\Factories\Factory;

class KunjunganFactory extends Factory
{
    protected $model = Kunjungan::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'nama_tamu' => fake()->name(),
            'no_whatsapp' => fake()->phoneNumber(),
            'jumlah_pengunjung' => fake()->numberBetween(1, 10),
            'maksud' => fake()->sentence(),
            'slot_waktu' => fake()->dateTimeBetween('+1 days', '+30 days'),
            'status' => fake()->randomElement(['PENDING', 'APPROVED', 'REJECTED']),
        ];
    }
}
