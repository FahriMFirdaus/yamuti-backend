<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KunjunganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_tamu' => ['required', 'string', 'max:255'],
            'no_whatsapp' => ['required', 'string', 'max:20'],
            'jumlah_pengunjung' => ['required', 'integer', 'min:1'],
            'maksud' => ['required', 'string'],
            'slot_waktu' => ['required', 'date', 'after:today'],
        ];
    }
}
