<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $settings = Setting::all();
        
        if (!$settings->contains('key', 'donasi_split_rule')) {
            $defaultRules = [
                ['nama_kas' => 'Yayasan Tasikmalaya', 'persentase' => 75],
                ['nama_kas' => 'Admin Sistem', 'persentase' => 10],
                ['nama_kas' => 'IT Support', 'persentase' => 5],
                ['nama_kas' => 'Operasional Website', 'persentase' => 10],
            ];
            $settings->push((object)[
                'key' => 'donasi_split_rule',
                'value' => $defaultRules
            ]);
        }

        return $this->successResponse($settings, 'Daftar pengaturan berhasil diambil');
    }

    public function updateSplitRule(Request $request): JsonResponse
    {
        $value = $request->input('value');
        
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $total = 0;
        if (is_array($value)) {
            foreach ($value as $item) {
                $total += (float) ($item['persentase'] ?? 0);
            }
            if (abs($total - 100) > 0.01) {
                return $this->errorResponse('Total persentase pembagian donasi harus tepat 100%', 422);
            }
        }

        $setting = Setting::updateOrCreate(
            ['key' => 'donasi_split_rule'],
            [
                'value' => $value,
                'type' => 'json',
                'description' => 'Pengaturan persentase pembagian donasi masuk'
            ]
        );

        return $this->successResponse($setting, 'Pengaturan Aturan Potongan Otomatis berhasil disimpan');
    }

    public function getLokasiOperasional(): JsonResponse
    {
        $setting = Setting::where('key', 'lokasi_dan_operasional')->first();

        $defaultData = [
            'email' => 'yatimmutiara@gmail.com',
            'no_hp_resmi' => '+62 877 0031 2182', // Nomor Telepon / HP Resmi Kantor Yayasan
            'no_whatsapp_admin' => '+62 877 0031 2182', // Nomor WhatsApp Admin (Ikon Mengambang & Chat Langsung)
            'no_whatsapp' => '+62 877 0031 2182',
            'alamat_singkat' => 'Sukahurip, Kab. Tasikmalaya, Jawa Barat',
            'alamat_lengkap' => 'JL. Letjen Masudi Pasar Gunung Kalong, Kec. Tamansari, Kota Tasikmalaya',
            'latitude' => -7.368327,
            'longitude' => 108.234632,
            'google_maps_url' => 'https://maps.google.com/?q=-7.368327,108.234632',
            'embed_map_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d989.2178069617374!2d108.23463186963221!3d-7.368327669509687!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f571bd02416e5%3A0xc424bdc4b9185c5f!2sYayasan%20Mutiara%20Titipan%20lllaahi%20YAMUTI%20Tasikmalaya!5e0!3m2!1sen!2ssg!4v1783591804254!5m2!1sen!2ssg',
            'jam_operasional_label' => 'Buka Setiap Hari',
            'jam_senin_jumat' => '08.00 - 17.00',
            'jam_sabtu_minggu' => '09.00 - 15.00',
            'jam_libur' => 'Sesuai Janji Temu',
            'slot_kunjungan_aktif' => ["08:00", "09:00", "10:00", "11:00", "13:00", "14:00", "15:00"],
        ];

        $data = $setting && $setting->value ? array_merge($defaultData, (array)$setting->value) : $defaultData;

        // Fallback jika belum diset terpisah
        if (empty($data['no_hp_resmi'])) {
            $data['no_hp_resmi'] = $data['no_whatsapp'] ?? '+62 877 0031 2182';
        }
        if (empty($data['no_whatsapp_admin'])) {
            $data['no_whatsapp_admin'] = $data['no_whatsapp'] ?? '+62 877 0031 2182';
        }

        return $this->successResponse($data, 'Data lokasi dan jam operasional yayasan');
    }

    public function updateLokasiOperasional(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'nullable|email|max:255',
            'no_hp_resmi' => 'nullable|string|max:50',
            'no_whatsapp_admin' => 'nullable|string|max:50',
            'no_whatsapp' => 'nullable|string|max:50',
            'alamat_singkat' => 'required|string|max:255',
            'alamat_lengkap' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'embed_map_url' => 'nullable|string',
            'google_maps_url' => 'nullable|string',
            'jam_operasional_label' => 'required|string|max:100',
            'jam_senin_jumat' => 'required|string|max:100',
            'jam_sabtu_minggu' => 'required|string|max:100',
            'jam_libur' => 'required|string|max:100',
            'slot_kunjungan_aktif' => 'nullable|array',
        ]);

        if (empty($validated['google_maps_url'])) {
            $validated['google_maps_url'] = "https://maps.google.com/?q={$validated['latitude']},{$validated['longitude']}";
        }

        if (empty($validated['embed_map_url'])) {
            $validated['embed_map_url'] = "https://maps.google.com/maps?q={$validated['latitude']},{$validated['longitude']}&z=17&output=embed";
        }

        $setting = Setting::updateOrCreate(
            ['key' => 'lokasi_dan_operasional'],
            [
                'value' => $validated,
                'type' => 'json',
                'description' => 'Pengaturan koordinat lokasi, alamat, dan jam buka yayasan'
            ]
        );

        // Sekaligus update jam_operasional_kunjungan jika ada slot_kunjungan_aktif
        if (!empty($validated['slot_kunjungan_aktif'])) {
            Setting::updateOrCreate(
                ['key' => 'jam_operasional_kunjungan'],
                [
                    'value' => $validated['slot_kunjungan_aktif'],
                    'type' => 'json',
                    'description' => 'Daftar slot jam kunjungan yang bisa dipilih tamu'
                ]
            );
        }

        return $this->successResponse($setting->value, 'Lokasi dan Jam Operasional berhasil diperbarui');
    }
}
