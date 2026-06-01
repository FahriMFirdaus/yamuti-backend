<?php

namespace Database\Factories;

use App\Models\AnakAsuh;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnakAsuhFactory extends Factory
{
    protected $model = AnakAsuh::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'nama' => fake()->name(),
            'tanggal_lahir' => fake()->dateTimeBetween('-18 years', '-1 year')->format('Y-m-d'),
            'status' => fake()->randomElement(['Aktif', 'Alumni']),
            'kategori_bayi' => fake()->boolean(20),
        ];
    }
}
