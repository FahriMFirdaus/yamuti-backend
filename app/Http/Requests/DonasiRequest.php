<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DonasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_donatur' => ['required', 'string', 'max:255'],
            'no_whatsapp' => ['nullable', 'string', 'max:20'],
            'gross_amount' => ['required', 'numeric', 'min:1000'],
            'payment_type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
