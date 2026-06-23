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
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'nama' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'nik' => [
                'nullable',
                'string',
                'size:16',
                \Illuminate\Validation\Rule::unique('anak_asuhs', 'nik')->ignore($this->route('id'))
            ],
            'no_kk' => ['nullable', 'string', 'size:16'],
            'no_akte' => ['nullable', 'string', 'max:255'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'jenis_kelamin' => ['nullable', 'string', 'in:Laki-laki,Perempuan'],
            'tanggal_lahir' => [$isUpdate ? 'sometimes' : 'required', 'date', 'before_or_equal:today'],
            'status' => [$isUpdate ? 'sometimes' : 'required', 'string', 'in:Aktif,Alumni'],
            'kategori_bayi' => [$isUpdate ? 'sometimes' : 'required', 'boolean'],
            'tanggal_masuk' => ['nullable', 'date', 'before_or_equal:today'],
            'keterangan' => ['nullable', 'string'],
            'foto_identitas' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
