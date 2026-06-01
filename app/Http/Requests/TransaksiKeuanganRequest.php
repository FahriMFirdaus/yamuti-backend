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
            'jenis_kas' => ['required', 'string', 'in:Pusat,Cabang'],
            'tipe_transaksi' => ['required', 'string', 'in:Debit,Kredit'],
            'nominal' => ['required', 'numeric', 'min:100'],
            'deskripsi' => ['required', 'string'],
        ];
    }
}
