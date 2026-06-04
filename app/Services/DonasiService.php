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

    public function updateStatus(string $id, string $status): Donasi
    {
        return DB::transaction(function () use ($id, $status) {
            $donasi = Donasi::findOrFail($id);
            
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
        $cabang = $nominal * 0.10; // 10% masuk ke Kas Cabang (Hak Amilin/Operasional)
        $pusat = $nominal * 0.90; // 90% masuk ke Kas Pusat

        // Mencatat Pemasukan Kas Pusat
        TransaksiKeuangan::create([
            'jenis_kas' => 'Pusat',
            'tipe_transaksi' => 'Debit',
            'nominal' => $pusat,
            'deskripsi' => 'Alokasi 90% dari Donasi oleh ' . $donasi->nama_donatur,
            'donasi_id' => $donasi->id,
        ]);

        // Mencatat Pemasukan Kas Cabang
        TransaksiKeuangan::create([
            'jenis_kas' => 'Cabang',
            'tipe_transaksi' => 'Debit',
            'nominal' => $cabang,
            'deskripsi' => 'Alokasi 10% dari Donasi oleh ' . $donasi->nama_donatur,
            'donasi_id' => $donasi->id,
        ]);
    }
}
