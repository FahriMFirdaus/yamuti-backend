<?php

namespace App\Services;

use App\Models\Donasi;
use App\Models\TransaksiKeuangan;
use App\Jobs\SendWhatsAppMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class DonasiService extends BaseService
{
    public function getAllDonasi(int $perPage = 15): LengthAwarePaginator
    {
        return Donasi::latest()->paginate($perPage);
    }

    public function getDonasiById(string $id): Donasi
    {
        return Donasi::findOrFail($id);
    }

    public function createDonasi(array $data, ?string $userId = null): Donasi
    {
        $data['user_id'] = $userId;
        $data['status'] = 'PENDING';
        
        $donasi = Donasi::create($data);

        // Integrasi Midtrans
        $midtransService = new MidtransService();
        $midtransData = $midtransService->createTransaction([
            'transaction_details' => [
                'order_id' => $donasi->id,
                'gross_amount' => $donasi->gross_amount,
            ],
            'customer_details' => [
                'first_name' => $donasi->nama_donatur,
                'phone' => $donasi->no_whatsapp,
            ]
        ]);

        $donasi->update([
            'snap_token' => $midtransData['snap_token'],
            'payment_url' => $midtransData['payment_url']
        ]);

        return $donasi;
    }

    public function createDonasiManual(array $data, ?string $userId = null): Donasi
    {
        $data['user_id'] = $userId;
        $data['status'] = 'PENDING'; // Tetap pending atau langsung PAID? Tergantung. Jika Admin yang input, bisa langsung PAID. Kita buat langsung PAID saja.
        $pt = strtoupper(trim($data['payment_type'] ?? ''));
        $data['payment_type'] = !empty($pt) ? $pt : 'CASH'; // Bisa CASH atau MANUAL_TRANSFER
        
        $donasi = Donasi::create($data);
        
        // Karena langsung PAID, jalankan split rule
        $donasi->update(['status' => 'PAID']);
        $this->applySplitRule($donasi);
        
        // Kirim notifikasi WA
        if ($donasi->no_whatsapp) {
            $pesan = "Alhamdulillah, donasi Tunai Anda sebesar Rp" . number_format($donasi->gross_amount, 0, ',', '.') . " telah kami terima dengan baik. Jazakumullah khairan katsiran.";
            SendWhatsAppMessage::dispatch($donasi->no_whatsapp, $pesan);
        }

        return $donasi;
    }

    public function updateStatus(string $id, string $status): Donasi
    {
        return DB::transaction(function () use ($id, $status) {
            $donasi = Donasi::lockForUpdate()->findOrFail($id);
            
            // Logika Otomatis: Jika status berubah jadi PAID untuk pertama kali, jalankan Split Rule
            if ($donasi->status !== 'PAID' && $status === 'PAID') {
                $donasi->update(['status' => 'PAID']);
                $this->applySplitRule($donasi);
                
                // Kirim notifikasi WA
                if ($donasi->no_whatsapp) {
                    $pesan = "Alhamdulillah, donasi Anda sebesar Rp" . number_format($donasi->gross_amount, 0, ',', '.') . " telah kami terima. Jazakumullah khairan katsiran.";
                    SendWhatsAppMessage::dispatch($donasi->no_whatsapp, $pesan);
                }
            } else {
                $donasi->update(['status' => $status]);
            }

            return $donasi;
        });
    }

    protected function applySplitRule(Donasi $donasi): void
    {
        $nominal = $donasi->gross_amount;
        $setting = \App\Models\Setting::where('key', 'donasi_split_rule')->first();
        
        $defaultRules = [
            ['nama_kas' => 'Yayasan Tasikmalaya', 'persentase' => 75],
            ['nama_kas' => 'Admin Sistem', 'persentase' => 10],
            ['nama_kas' => 'IT Support', 'persentase' => 5],
            ['nama_kas' => 'Operasional Website', 'persentase' => 10],
        ];

        $rules = $setting && is_array($setting->value) ? $setting->value : $defaultRules;

        foreach ($rules as $rule) {
            $persentase = floatval($rule['persentase']);
            if ($persentase <= 0) continue;

            $alokasi = $nominal * ($persentase / 100);

            TransaksiKeuangan::create([
                'jenis_kas' => $rule['nama_kas'] ?? 'Umum',
                'tipe_transaksi' => 'Debit',
                'nominal' => $alokasi,
                'deskripsi' => 'Alokasi ' . $persentase . '% dari Donasi ' . $donasi->nama_donatur,
                'donasi_id' => $donasi->id,
            ]);
        }
    }
}
