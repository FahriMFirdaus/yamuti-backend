<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Donasi;
use App\Models\Setting;
use App\Models\TransaksiKeuangan;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DonasiService extends BaseService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }
    public function getAllDonasi(int $perPage = 15, ?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        $query = Donasi::with(['kampanye:id,judul,slug', 'user:id,name,email']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_donatur', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('order_id', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if (! empty($status) && $status !== 'ALL') {
            $query->where('status', strtoupper($status));
        }

        return $query->latest()->paginate($perPage);
    }

    public function getDonasiById(string $id): Donasi
    {
        return Donasi::with(['kampanye:id,judul,slug,target_donasi,dana_terkumpul', 'user:id,name,email,no_hp'])
            ->findOrFail($id);
    }

    public function createDonasi(array $data, ?string $userId = null): Donasi
    {
        $data['user_id'] = $userId;
        $data['status'] = 'PENDING';

        // Jika donatur memilih transfer manual dan mengunggah bukti transfer
        $isManualTransfer = isset($data['payment_type']) && strtoupper($data['payment_type']) === 'MANUAL_TRANSFER';

        $donasi = Donasi::create($data);
        $donasi->loadMissing('kampanye');

        $itemName = 'Donasi Umum';
        if ($donasi->kampanye) {
            $itemName = $donasi->kampanye->judul;
        } elseif (! empty($donasi->orang_tua_asuh_id)) {
            $donasi->loadMissing('orangTuaAsuh.anakAsuh');
            $namaAnak = $donasi->orangTuaAsuh?->anakAsuh?->nama ?? 'Anak Asuh';
            $itemName = "Beasiswa POTA: {$namaAnak}";
        } elseif ($donasi->pesan && stripos($donasi->pesan, '[Zakat') !== false) {
            if (stripos($donasi->pesan, 'Profesi') !== false) {
                $itemName = 'Zakat Profesi';
            } elseif (stripos($donasi->pesan, 'Maal') !== false) {
                $itemName = 'Zakat Maal';
            } elseif (stripos($donasi->pesan, 'Fitrah') !== false) {
                $itemName = 'Zakat Fitrah';
            } else {
                $itemName = 'Zakat';
            }
        }

        // Jika transfer manual dengan bukti transfer, tidak perlu generate Midtrans token
        if ($isManualTransfer && ! empty($donasi->bukti_transfer)) {
            if ($donasi->no_whatsapp) {
                $formattedAmount = 'Rp'.number_format($donasi->gross_amount, 0, ',', '.');
                $pesan = "Halo kak *{$donasi->nama_donatur}*,\n\nTerima kasih, bukti transfer donasi Anda sebesar *{$formattedAmount}* ({$itemName}) telah kami terima.\n\nNomor Transaksi: *#{$donasi->id}*\nStatus: *Menunggu Verifikasi Admin*\n\nPengurus yayasan akan segera memverifikasi mutasi rekening Anda. Semoga Allah membalas kebaikan Anda sekeluarga.";
                SendWhatsAppMessage::dispatch($donasi->no_whatsapp, $pesan);
            }

            return $donasi;
        }

        // Integrasi Midtrans untuk pembayaran online otomatis
        $midtransService = app(MidtransService::class);
        $midtransData = $midtransService->createTransaction([
            'transaction_details' => [
                'order_id' => $donasi->id,
                'gross_amount' => $donasi->gross_amount,
            ],
            'item_details' => [
                [
                    'id' => $donasi->id,
                    'price' => (int) $donasi->gross_amount,
                    'quantity' => 1,
                    'name' => Str::limit($itemName, 50),
                ],
            ],
            'customer_details' => [
                'first_name' => $donasi->nama_donatur,
                'phone' => $donasi->no_whatsapp,
            ],
        ]);

        $donasi->update([
            'snap_token' => $midtransData['snap_token'],
            'payment_url' => $midtransData['payment_url'],
        ]);

        // Kirim notifikasi WA tahap 1 (PENDING: instruksi bayar)
        if ($donasi->no_whatsapp) {
            $formattedAmount = 'Rp'.number_format($donasi->gross_amount, 0, ',', '.');
            $pesan = "Halo kak *{$donasi->nama_donatur}*,\n\nTerima kasih atas niat baik Anda untuk berdonasi sebesar *{$formattedAmount}* ({$itemName}) melalui YAMUTI.\n\nNomor Transaksi: *#{$donasi->id}*\nStatus: *Menunggu Pembayaran*\n\nSilakan selesaikan pembayaran melalui tautan berikut:\n{$donasi->payment_url}\n\nSemoga Allah membalas kebaikan Anda sekeluarga.";
            SendWhatsAppMessage::dispatch($donasi->no_whatsapp, $pesan);
        }

        return $donasi;
    }

    public function createDonasiManual(array $data, ?string $userId = null): Donasi
    {
        $data['user_id'] = $userId;
        $data['status'] = 'PENDING'; // Tetap pending atau langsung PAID? Tergantung. Jika Admin yang input, bisa langsung PAID. Kita buat langsung PAID saja.
        $pt = strtoupper(trim($data['payment_type'] ?? ''));
        $data['payment_type'] = ! empty($pt) ? $pt : 'CASH'; // Bisa CASH atau MANUAL_TRANSFER

        $donasi = Donasi::create($data);

        // Karena langsung PAID, jalankan split rule & pencatatan jurnal akuntansi
        $donasi->update(['status' => 'PAID']);
        $this->applySplitRule($donasi);
        try {
            $this->accountingService->recordDonasiJournal($donasi);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal auto-journal donasi: ' . $e->getMessage());
        }

        // Kirim notifikasi WA
        if ($donasi->no_whatsapp) {
            $pesan = 'Alhamdulillah, donasi Tunai Anda sebesar Rp'.number_format($donasi->gross_amount, 0, ',', '.').' telah kami terima dengan baik. Jazakumullah khairan katsiran.';
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
                try {
                    $this->accountingService->recordDonasiJournal($donasi);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal auto-journal donasi: ' . $e->getMessage());
                }

                // Kirim notifikasi WA
                if ($donasi->no_whatsapp) {
                    $pesan = 'Alhamdulillah, donasi Anda sebesar Rp'.number_format($donasi->gross_amount, 0, ',', '.').' telah kami terima. Jazakumullah khairan katsiran.';
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
        $setting = Setting::where('key', 'donasi_split_rule')->first();

        $defaultRules = [
            ['nama_kas' => 'Yayasan Tasikmalaya', 'persentase' => 75],
            ['nama_kas' => 'Admin Sistem', 'persentase' => 10],
            ['nama_kas' => 'IT Support', 'persentase' => 5],
            ['nama_kas' => 'Operasional Website', 'persentase' => 10],
        ];

        $rules = $setting && is_array($setting->value) ? $setting->value : $defaultRules;

        foreach ($rules as $rule) {
            $persentase = floatval($rule['persentase']);
            if ($persentase <= 0) {
                continue;
            }

            $alokasi = $nominal * ($persentase / 100);

            TransaksiKeuangan::create([
                'jenis_kas' => $rule['nama_kas'] ?? 'Umum',
                'tipe_transaksi' => 'Debit',
                'nominal' => $alokasi,
                'deskripsi' => 'Alokasi '.$persentase.'% dari Donasi '.$donasi->nama_donatur,
                'donasi_id' => $donasi->id,
            ]);
        }
    }
}
