<?php

namespace Database\Factories;

use App\Models\Donasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class DonasiFactory extends Factory
{
    protected $model = Donasi::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'nama_donatur' => fake()->name(),
            'no_whatsapp' => fake()->phoneNumber(),
            'gross_amount' => fake()->randomFloat(2, 50000, 10000000),
            'status' => fake()->randomElement(['PENDING', 'PAID', 'FAILED']),
            'payment_type' => fake()->randomElement(['qris', 'bank_transfer', 'ewallet']),
            'transaction_id' => fake()->uuid(),
        ];
    }
}
