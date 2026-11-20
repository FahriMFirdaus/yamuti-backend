<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransaksiKeuanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis_kas' => ['required', 'string'],
            'tipe_transaksi' => ['required', 'string', 'in:Debit,Kredit'],
            'kategori_pengeluaran' => ['nullable', 'string', 'max:100'],
            'nominal' => ['required', 'numeric', 'min:100'],
            'deskripsi' => ['required', 'string'],
            'bukti_transaksi' => ['nullable', 'string'],
            'kampanye_id' => ['nullable', 'exists:kampanyes,id'],
        ];
    }
}
