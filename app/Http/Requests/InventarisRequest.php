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
        return [
            'nama_barang' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'stok_sekarang' => ['required', 'integer', 'min:0'],
            'satuan' => ['required', 'string', 'max:50'],
        ];
    }
}
