<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventarisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'nama_barang' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'in:Sembako,Obat-obatan,Pakaian,Perlengkapan Sekolah,Elektronik,Lainnya'],
            'deskripsi' => ['nullable', 'string'],
            'stok_sekarang' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:0'],
            'satuan' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],
            'tanggal_kadaluarsa' => ['nullable', 'date'],
            'lokasi_penyimpanan' => ['nullable', 'string', 'max:100'],
            'stok_minimum' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
