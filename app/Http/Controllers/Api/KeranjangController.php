<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\Keranjang;
use App\Models\Donasi;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class KeranjangController extends Controller
{
    use ApiResponse;

    public function count(Request $request)
    {
        $count = Keranjang::where('user_id', $request->user()->id)->count();
        return $this->successResponse(['count' => $count], 'Berhasil mengambil jumlah keranjang');
    }

    public function index(Request $request)
    {
        $items = Keranjang::with('kampanye:id,judul,slug')->where('user_id', $request->user()->id)->latest()->get();
        return $this->successResponse($items, 'Berhasil mengambil data keranjang');
    }

    public function store(Request $request)
    {
        // Support both nominal and gross_amount from frontend
        if (!$request->has('nominal') && $request->has('gross_amount')) {
            $request->merge(['nominal' => $request->gross_amount]);
        }

        $validated = $request->validate([
            'kampanye_id' => 'nullable|exists:kampanyes,id',
            'nominal' => 'required|numeric|min:10000',
            'pesan' => 'nullable|string|max:500',
            'is_anonymous' => 'nullable|boolean',
            'nama_donatur' => 'nullable|string|max:255',
            'no_whatsapp' => 'nullable|string|max:20',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['nama_donatur'] = $validated['nama_donatur'] ?? $request->user()->name;
        $validated['no_whatsapp'] = $validated['no_whatsapp'] ?? $request->user()->no_hp;

        Keranjang::create($validated);
        return $this->successResponse(null, 'Item berhasil ditambahkan ke keranjang', 201);
    }

    public function destroy(Request $request, $id)
    {
        $keranjang = Keranjang::where('user_id', $request->user()->id)->findOrFail($id);
        $keranjang->delete();
        return $this->successResponse(null, 'Item berhasil dihapus dari keranjang');
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'keranjang_ids' => 'required|array',
            'keranjang_ids.*' => 'required|exists:keranjangs,id'
        ]);

        $items = Keranjang::whereIn('id', $validated['keranjang_ids'])
            ->where('user_id', $request->user()->id)
            ->get();

        if ($items->isEmpty()) {
            return $this->errorResponse('Tidak ada item yang dicheckout', 400);
        }

        return DB::transaction(function () use ($items, $request) {
            $paymentGroupId = 'GRP-' . Str::uuid()->toString();
            $totalAmount = $items->sum('nominal');

            // Kita buat array order detail untuk midtrans
            $itemDetails = [];
            $donasis = [];

            foreach ($items as $item) {
                $donasi = Donasi::create([
                    'payment_group_id' => $paymentGroupId,
                    'user_id' => $request->user()->id,
                    'kampanye_id' => $item->kampanye_id,
                    'nama_donatur' => $item->nama_donatur,
                    'no_whatsapp' => $item->no_whatsapp,
                    'pesan' => $item->pesan,
                    'is_anonymous' => $item->is_anonymous,
                    'gross_amount' => $item->nominal,
                    'status' => 'PENDING',
                ]);
                $donasis[] = $donasi;

                $itemDetails[] = [
                    'id' => $donasi->id,
                    'price' => $item->nominal,
                    'quantity' => 1,
                    'name' => 'Donasi ' . ($item->kampanye ? Str::limit($item->kampanye->judul, 30) : 'Umum')
                ];
            }

            // Integrasi Midtrans
            $midtransService = new MidtransService();
            $midtransData = $midtransService->createTransaction([
                'transaction_details' => [
                    'order_id' => $paymentGroupId, // 1 ID Induk untuk semua item
                    'gross_amount' => $totalAmount,
                ],
                'item_details' => $itemDetails,
                'customer_details' => [
                    'first_name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->no_hp,
                ]
            ]);

            // Update Snap Token ke semua donasi yang ada di grup ini
            Donasi::where('payment_group_id', $paymentGroupId)->update([
                'snap_token' => $midtransData['snap_token'],
                'payment_url' => $midtransData['payment_url']
            ]);

            // Hapus item dari keranjang
            Keranjang::whereIn('id', $items->pluck('id'))->delete();

            return $this->successResponse([
                'payment_group_id' => $paymentGroupId,
                'snap_token' => $midtransData['snap_token'],
                'payment_url' => $midtransData['payment_url'],
                'total_amount' => $totalAmount
            ], 'Checkout keranjang berhasil');
        });
    }
}
