<?php

namespace Database\Seeders;

use App\Models\AnakAsuh;
use Illuminate\Database\Seeder;

class AnakAsuhSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AnakAsuh::factory()->count(30)->create();
    }
}
