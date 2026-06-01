<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnakAsuhRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', 'string', 'in:Aktif,Alumni'],
            'kategori_bayi' => ['required', 'boolean'],
        ];
    }
}
