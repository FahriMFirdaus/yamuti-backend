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
            'slot_waktu' => [
                'required',
                'date',
                'after:today',
                function ($attribute, $value, $fail) {
                    $time = \Carbon\Carbon::parse($value);
                    
                    // Cek apakah ada jadwal kunjungan lain (PENDING atau APPROVED) dalam radius 2 jam
                    $conflict = \App\Models\Kunjungan::whereIn('status', ['PENDING', 'APPROVED'])
                        ->whereBetween('slot_waktu', [
                            $time->copy()->subHours(2),
                            $time->copy()->addHours(2)
                        ])->exists();

                    if ($conflict) {
                        $fail('Jadwal kunjungan pada waktu tersebut sudah penuh atau berbenturan dengan tamu lain. Mohon pilih waktu atau hari lain (minimal jeda 2 jam).');
                    }
                },
            ],
        ];
    }
}
