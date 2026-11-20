<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Kunjungan;
use Illuminate\Pagination\LengthAwarePaginator;

class KunjunganService extends BaseService
{
    public function getAllKunjungan(int $perPage = 15): LengthAwarePaginator
    {
        return Kunjungan::latest()->paginate($perPage);
    }

    public function getKunjunganById(string $id): Kunjungan
    {
        return Kunjungan::findOrFail($id);
    }

    public function requestKunjungan(array $data): Kunjungan
    {
        $data['status'] = 'PENDING';

        return Kunjungan::create($data);
    }

    public function approveKunjungan(string $id, string $adminId, ?string $catatanAdmin = null): Kunjungan
    {
        $kunjungan = Kunjungan::findOrFail($id);
        $kunjungan->update([
            'status' => 'APPROVED',
            'approved_by' => $adminId,
            'catatan_admin' => $catatanAdmin,
        ]);

        $formattedWaktu = $kunjungan->slot_waktu ? $kunjungan->slot_waktu->format('d M Y, H:i') : '';
        $pesan = "Assalamu'alaikum Wr. Wb. Bpk/Ibu *{$kunjungan->nama_tamu}*,\n\nAlhamdulillah, permohonan kunjungan Anda ke Panti Asuhan YAMUTI untuk jadwal *{$formattedWaktu} WIB* telah *DISETUJUI*.\n\nJumlah Rombongan: {$kunjungan->jumlah_pengunjung} Orang\nLokasi: Yayasan Mutiara Titipan Ilahi (YAMUTI)\n\nHarap hadir tepat waktu sesuai jadwal. Jika ada perubahan atau kebutuhan informasi lebih lanjut, silakan hubungi kami melalui nomor ini. Terima kasih.";
        if (! empty($catatanAdmin)) {
            $pesan .= "\n\nCatatan Pengurus: _{$catatanAdmin}_";
        }
        SendWhatsAppMessage::dispatch($kunjungan->no_whatsapp, $pesan);

        return $kunjungan;
    }

    public function rejectKunjungan(string $id, string $adminId, ?string $catatanAdmin = null): Kunjungan
    {
        $kunjungan = Kunjungan::findOrFail($id);
        $kunjungan->update([
            'status' => 'REJECTED',
            'approved_by' => $adminId,
            'catatan_admin' => $catatanAdmin,
        ]);

        $formattedWaktu = $kunjungan->slot_waktu ? $kunjungan->slot_waktu->format('d M Y, H:i') : '';
        $pesan = "Assalamu'alaikum Wr. Wb. Bpk/Ibu *{$kunjungan->nama_tamu}*,\n\nMohon maaf, permohonan jadwal kunjungan Anda ke Panti Asuhan YAMUTI pada *{$formattedWaktu} WIB* belum dapat kami setujui saat ini.";
        if (! empty($catatanAdmin)) {
            $pesan .= "\n\n*Alasan Penolakan:*\n\"{$catatanAdmin}\"";
        }
        $pesan .= "\n\nAnda dipersilakan untuk memilih atau mengajukan kembali jadwal di hari/jam lain melalui website kami. Terima kasih atas pengertian dan niat baik Anda.";

        SendWhatsAppMessage::dispatch($kunjungan->no_whatsapp, $pesan);

        return $kunjungan;
    }
}
