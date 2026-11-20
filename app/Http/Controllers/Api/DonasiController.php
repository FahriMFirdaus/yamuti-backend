<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonasiRequest;
use App\Models\Donasi;
use App\Services\DonasiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonasiController extends Controller
{
    use ApiResponse;

    protected DonasiService $donasiService;

    public function __construct(DonasiService $donasiService)
    {
        $this->donasiService = $donasiService;
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->donasiService->getAllDonasi((int) $request->query('per_page', 15));

        return $this->successResponse($data, 'Data donasi berhasil diambil');
    }

    public function show($id): JsonResponse
    {
        $data = $this->donasiService->getDonasiById($id);

        return $this->successResponse($data, 'Detail donasi berhasil diambil');
    }

    public function store(DonasiRequest $request): JsonResponse
    {
        // Donatur bisa saja tamu yang tidak login, jadi id bersifat opsional
        $userId = $request->user('sanctum')?->id;

        $validatedData = $request->validated();
        if (isset($validatedData['is_anonymous'])) {
            $validatedData['is_anonymous'] = filter_var($validatedData['is_anonymous'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        } else {
            $validatedData['is_anonymous'] = 'false';
        }

        // Upload bukti transfer jika dilampirkan
        if ($request->hasFile('bukti_transfer')) {
            try {
                $path = $request->file('bukti_transfer')->store('donasi/bukti', 's3');
            } catch (\Throwable $e) {
                $path = $request->file('bukti_transfer')->store('donasi/bukti', 'public');
            }
            $validatedData['bukti_transfer'] = $path;
        }

        $data = $this->donasiService->createDonasi($validatedData, $userId);

        return $this->successResponse($data, 'Donasi berhasil dicatat', 201);
    }

    public function storeManual(DonasiRequest $request): JsonResponse
    {
        // Khusus untuk Admin yang menginput donasi tunai/cash
        $userId = $request->user('sanctum')?->id;
        $validatedData = $request->validated();

        // Upload bukti transfer fisik jika ada
        if ($request->hasFile('bukti_transfer')) {
            try {
                $path = $request->file('bukti_transfer')->store('donasi/bukti', 's3');
            } catch (\Throwable $e) {
                $path = $request->file('bukti_transfer')->store('donasi/bukti', 'public');
            }
            $validatedData['bukti_transfer'] = $path;
        }

        $data = $this->donasiService->createDonasiManual($validatedData, $userId);

        return $this->successResponse($data, 'Donasi manual berhasil dicatat dan diverifikasi', 201);
    }

    public function verify($id): JsonResponse
    {
        // Admin memverifikasi bukti donasi
        $data = $this->donasiService->updateStatus($id, 'PAID');

        return $this->successResponse($data, 'Donasi berhasil diverifikasi dan dana telah dialokasikan (Split 10-90)');
    }

    public function riwayat(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = (int) $request->query('per_page', 10);
        if ($perPage <= 0 || $perPage > 100) {
            $perPage = 10;
        }

        $query = Donasi::where('user_id', $userId)->with('kampanye:id,judul,slug');

        // Optional filter status
        $status = $request->query('status');
        if ($status) {
            if (in_array(strtolower($status), ['berhasil', 'paid', 'success', 'settlement'])) {
                $query->whereIn('status', ['PAID', 'paid', 'success', 'settlement', 'Berhasil']);
            } elseif (in_array(strtolower($status), ['pending', 'menunggu'])) {
                $query->whereIn('status', ['PENDING', 'pending', 'Menunggu']);
            } else {
                $query->where('status', $status);
            }
        }

        // Optional search query
        $q = $request->query('q');
        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('invoice', 'like', "%{$q}%")
                  ->orWhere('id', 'like', "%{$q}%")
                  ->orWhereHas('kampanye', function ($kq) use ($q) {
                      $kq->where('judul', 'like', "%{$q}%");
                  });
            });
        }

        $data = $query->latest()->paginate($perPage);

        return $this->successResponse($data, 'Riwayat donasi berhasil diambil');
    }
}
