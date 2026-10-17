<?php

namespace Database\Factories;

use App\Models\Inventaris;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventarisFactory extends Factory
{
    protected $model = Inventaris::class;

    public function definition(): array
    {
        $items = [
            'Beras' => ['satuan' => 'kg', 'deskripsi' => 'Beras untuk konsumsi anak-anak asuh'],
            'Minyak Goreng' => ['satuan' => 'liter', 'deskripsi' => 'Minyak goreng kelapa sawit untuk dapur'],
            'Gula Pasir' => ['satuan' => 'kg', 'deskripsi' => 'Gula pasir untuk dapur'],
            'Susu Bayi' => ['satuan' => 'box', 'deskripsi' => 'Susu formula untuk kategori bayi'],
            'Kasur Busa' => ['satuan' => 'pcs', 'deskripsi' => 'Kasur tidur anak asuh'],
            'Bantal' => ['satuan' => 'pcs', 'deskripsi' => 'Bantal tidur anak asuh'],
            'Meja Belajar' => ['satuan' => 'pcs', 'deskripsi' => 'Meja belajar lipat untuk belajar'],
            'Al-Qur\'an' => ['satuan' => 'pcs', 'deskripsi' => 'Al-Qur\'an hafalan anak asuh'],
            'Buku Tulis' => ['satuan' => 'pack', 'deskripsi' => 'Buku tulis sekolah'],
            'Seragam Sekolah' => ['satuan' => 'pcs', 'deskripsi' => 'Seragam sekolah anak asuh'],
            'Mie Instan' => ['satuan' => 'box', 'deskripsi' => 'Cadangan logistik dapur umum'],
            'Pampers Bayi' => ['satuan' => 'pack', 'deskripsi' => 'Pampers untuk kategori bayi/balita'],
        ];

        $namaBarang = fake()->randomElement(array_keys($items));
        $itemDetails = $items[$namaBarang];

        return [
            'id' => fake()->uuid(),
            'nama_barang' => $namaBarang,
            'deskripsi' => $itemDetails['deskripsi'],
            'stok_sekarang' => fake()->numberBetween(5, 50),
            'satuan' => $itemDetails['satuan'],
        ];
    }
}
