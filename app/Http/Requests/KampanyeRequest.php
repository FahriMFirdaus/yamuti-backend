<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KampanyeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'target_donasi' => 'required|numeric|min:0',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_berakhir' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status' => 'nullable|string|in:Aktif,Selesai,Dibatalkan',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }
}
