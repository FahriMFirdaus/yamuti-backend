<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DonaturController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        // Hanya ambil user yang memiliki role 'donatur'
        $perPage = $request->query('per_page', 15);
        $donaturs = User::role('donatur')
            ->withCount('donasis')
            ->latest()
            ->paginate($perPage);

        return $this->successResponse($donaturs, 'Daftar donatur berhasil diambil');
    }

    public function show($id): JsonResponse
    {
        $donatur = User::role('donatur')->with('donasis.kampanye')->findOrFail($id);
        return $this->successResponse($donatur, 'Detail donatur berhasil diambil');
    }
}
