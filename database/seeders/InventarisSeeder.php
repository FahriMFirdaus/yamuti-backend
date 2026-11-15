<?php

namespace Database\Seeders;

use App\Models\Inventaris;
use Illuminate\Database\Seeder;

class InventarisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Inventaris::factory()->count(15)->create();
    }
}
