<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'nik' => ['nullable', 'string', 'size:16', 'unique:users,nik'],
            'no_hp' => ['nullable', 'string', 'max:255'],
            'skck' => ['nullable', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'status_pegawai' => ['nullable', 'string', 'in:Aktif,Nonaktif'],
        ];
    }
}
