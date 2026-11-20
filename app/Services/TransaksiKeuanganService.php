<?php

namespace App\Services;

use App\Models\TransaksiKeuangan;
use Illuminate\Pagination\LengthAwarePaginator;

class TransaksiKeuanganService extends BaseService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function getAllTransaksi(int $perPage = 15): LengthAwarePaginator
    {
        return TransaksiKeuangan::with(['kampanye:id,judul'])->latest()->paginate($perPage);
    }

    public function getTransaksiById(string $id): TransaksiKeuangan
    {
        return TransaksiKeuangan::with(['kampanye:id,judul'])->findOrFail($id);
    }

    public function createTransaksi(array $data, string $userId): TransaksiKeuangan
    {
        $data['created_by'] = $userId;
        $kas = TransaksiKeuangan::create($data);

        // Otomatis catat Jurnal Akuntansi (Double-Entry)
        try {
            $this->accountingService->recordKasJournal($kas);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal auto-journal kas: ' . $e->getMessage());
        }

        return $kas;
    }

    public function getSaldo(?string $jenisKas = null): float
    {
        $query = TransaksiKeuangan::query();
        if ($jenisKas) {
            $query->where('jenis_kas', $jenisKas);
        }

        $debit = (clone $query)->where('tipe_transaksi', 'Debit')->sum('nominal');
        $kredit = (clone $query)->where('tipe_transaksi', 'Kredit')->sum('nominal');

        return $debit - $kredit;
    }
}
