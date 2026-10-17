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
            'tanggal_masuk' => [$isUpdate ? 'sometimes' : 'required', 'date', 'before_or_equal:today'],
            'keterangan' => ['nullable', 'string'],
            'foto_identitas' => ['nullable', 'image', 'max:5120'],
            'status_keluarga' => [$isUpdate ? 'sometimes' : 'required', 'string', 'in:Yatim,Piatu,Yatim Piatu,Dhuafa'],
            'tingkat_pendidikan' => ['nullable', 'string', 'in:Belum Sekolah,TK/PAUD,SD,SMP,SMA/SMK,Kuliah'],
            'nama_sekolah' => ['nullable', 'string', 'max:255'],
            'golongan_darah' => ['nullable', 'string', 'in:A,B,AB,O,Belum Tahu'],
            'riwayat_penyakit' => ['nullable', 'string'],
            'catatan_khusus' => ['nullable', 'string'],
            'no_bpjs' => ['nullable', 'string', 'max:50'],
            'has_akta_fisik' => ['nullable', 'boolean'],
            'has_kk_fisik' => ['nullable', 'boolean'],
            'has_ijazah_fisik' => ['nullable', 'boolean'],
            'berat_badan' => ['nullable', 'integer', 'min:1', 'max:200'],
            'tinggi_badan' => ['nullable', 'integer', 'min:30', 'max:250'],
            'riwayat_alergi' => ['nullable', 'string'],
            'status_imunisasi' => ['nullable', 'string'],
        ];
    }
}
