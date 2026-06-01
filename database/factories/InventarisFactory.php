<?php

namespace Database\Factories;

use App\Models\Inventaris;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventarisFactory extends Factory
{
    protected $model = Inventaris::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'nama_barang' => fake()->word(),
            'deskripsi' => fake()->sentence(),
            'stok_sekarang' => fake()->numberBetween(10, 100),
            'satuan' => fake()->randomElement(['pcs', 'kg', 'box', 'liter']),
        ];
    }
}
