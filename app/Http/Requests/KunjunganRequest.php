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
                    $isFullDay = request()->input('is_full_day', false);
                    
                    if ($isFullDay) {
                        // Jika minta full day, pastikan tidak ada kunjungan APAPUN di hari tersebut
                        $conflict = \App\Models\Kunjungan::whereIn('status', ['PENDING', 'APPROVED'])
                            ->whereDate('slot_waktu', $time->toDateString())
                            ->exists();
                            
                        if ($conflict) {
                            $fail('Hari tersebut sudah memiliki jadwal kunjungan parsial. Tidak bisa booking sehari penuh.');
                        }
                    } else {
                        // Jika kunjungan biasa, pastikan tidak bentrok dengan kunjungan Full Day di hari yang sama
                        $fullDayConflict = \App\Models\Kunjungan::whereIn('status', ['PENDING', 'APPROVED'])
                            ->whereDate('slot_waktu', $time->toDateString())
                            ->where('is_full_day', 'true')
                            ->exists();
                            
                        if ($fullDayConflict) {
                            $fail('Hari tersebut sudah dibooking penuh (Full Day) oleh tamu lain.');
                        }
                        
                        // Cek bentrok jam (radius 2 jam) untuk kunjungan biasa
                        $conflict = \App\Models\Kunjungan::whereIn('status', ['PENDING', 'APPROVED'])
                            ->where('is_full_day', 'false')
                            ->whereBetween('slot_waktu', [
                                $time->copy()->subHours(2),
                                $time->copy()->addHours(2)
                            ])->exists();
    
                        if ($conflict) {
                            $fail('Jadwal kunjungan pada waktu tersebut sudah penuh atau berbenturan dengan tamu lain. Mohon pilih waktu atau hari lain (minimal jeda 2 jam).');
                        }
                    }
                },
            ],
            'is_full_day' => ['nullable', 'boolean'],
        ];
    }
}
