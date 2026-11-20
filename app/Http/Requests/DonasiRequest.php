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
            'no_whatsapp' => ['required', 'string', 'min:9', 'max:15'],
            'gross_amount' => ['required', 'numeric', 'min:10000'],
            'payment_type' => ['nullable', 'string', 'max:100'],
            'kampanye_id' => ['nullable', 'exists:kampanyes,id'],
            'orang_tua_asuh_id' => ['nullable', 'exists:orang_tua_asuhs,id'],
            'pesan' => ['nullable', 'string', 'max:500'],
            'bukti_transfer' => ['nullable', 'file', 'image', 'max:5120'],
            'is_anonymous' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('is_anonymous')) {
            $this->merge([
                'is_anonymous' => filter_var($this->is_anonymous, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }
    }
}
