<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnakAsuh;
use App\Models\Donasi;
use App\Models\Kampanye;
use App\Models\Galeri;
use Illuminate\Http\JsonResponse;

class LandingController extends Controller
{
    /**
     * API 1: Statistik Dampak (Impact)
     */
    public function impact(): JsonResponse
    {
        $jiwaTerbantu = AnakAsuh::count();
        $danaTersalurkan = Donasi::whereIn('status', ['PAID', 'settlement', 'success'])->sum('gross_amount');
        $programBerjalan = Kampanye::where('status', 'Aktif')->count();
        $totalDonatur = Donasi::whereIn('status', ['PAID', 'settlement', 'success'])
            ->select('nama_donatur')
            ->distinct()
            ->count();

        // Format dana tersalurkan agar ringkas
        $formattedDana = $this->formatRupiah($danaTersalurkan);

        return response()->json([
            "jiwaterbantu" => $jiwaTerbantu > 0 ? $jiwaTerbantu . "+" : "30+",
            "danatersalurkan" => $danaTersalurkan > 0 ? $formattedDana : "Rp 0",
            "programberjalan" => $programBerjalan > 0 ? $programBerjalan . "+" : "5+",
            "total_donatur" => $totalDonatur > 0 ? $totalDonatur : 15,
            "pengabdian" => "12 Tahun"
        ]);
    }

    /**
     * Helper untuk memformat nominal
     */
    private function formatRupiah($angka)
    {
        if ($angka >= 1000000000) {
            return 'Rp ' . round($angka / 1000000000, 1) . ' Miliar';
        } elseif ($angka >= 1000000) {
            return 'Rp ' . round($angka / 1000000, 1) . ' Juta';
        }
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }

    /**
     * API 2: Alasan/Benefit Yayasan (Foundation Profile)
     */
    public function benefits(): JsonResponse
    {
        return response()->json([
            [ 
                "id" => "1", 
                "title" => "Transparansi", 
                "description" => "Laporan keuangan dan program yang transparan", 
                "iconName" => "ShieldCheck" 
            ],
            [ 
                "id" => "2", 
                "title" => "Aksesibilitas", 
                "description" => "Kemudahan akses program dan donasi", 
                "iconName" => "CheckCircle2" 
            ],
            [ 
                "id" => "3", 
                "title" => "Komunitas", 
                "description" => "Jaringan donatur dan relawan yang solid", 
                "iconName" => "Users" 
            ],
            [ 
                "id" => "4", 
                "title" => "Dampak Nyata", 
                "description" => "Program terukur untuk penerima manfaat", 
                "iconName" => "Heart" 
            ]
        ]);
    }

    /**
     * API 3: Hero Slider
     */
    public function slides(): JsonResponse
    {
        // Ambil 3 gambar galeri terbaru untuk dijadikan slider landing page
        $galeris = Galeri::latest()->take(3)->get();
        
        $slides = $galeris->map(function ($galeri, $index) {
            return [
                "id" => (string)($index + 1),
                "src" => $galeri->foto_url,
                "alt" => $galeri->judul ?? "Kegiatan YAMUTI"
            ];
        });

        // Jika galeri kosong, berikan fallback agar slider frontend tidak error
        if ($slides->isEmpty()) {
            $slides = [
                [
                    "id" => "1",
                    "src" => "https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=2070&auto=format&fit=crop",
                    "alt" => "Bantu Mereka Tersenyum"
                ],
                [
                    "id" => "2",
                    "src" => "https://images.unsplash.com/photo-1593113563332-e147ce10094b?q=80&w=2070&auto=format&fit=crop",
                    "alt" => "Kegiatan Belajar"
                ]
            ];
        }

        return response()->json($slides);
    }

    /**
     * API 4: Titipan Doa Donatur
     */
    public function doa(): JsonResponse
    {
        $doas = Donasi::whereIn('status', ['PAID', 'settlement', 'success'])
            ->whereNotNull('pesan')
            ->where('pesan', '!=', '')
            ->latest()
            ->take(20)
            ->get();

        $result = $doas->map(function ($item) {
            $isAnon = $item->is_anonymous;
            $namaAsli = trim($item->nama_donatur);

            if ($isAnon || strtolower($namaAsli) === 'hamba allah') {
                $namaTampil = 'Hamba Allah';
                $roleTampil = 'Hamba Allah';
            } else {
                // Samarkan nama seperti B*** T. atau Budi S***
                $parts = explode(' ', $namaAsli);
                if (count($parts) > 1) {
                    $first = $parts[0];
                    $second = $parts[1];
                    $firstMasked = mb_substr($first, 0, 1) . '***';
                    $secondInitial = mb_substr($second, 0, 1) . '.';
                    $namaTampil = $firstMasked . ' ' . $secondInitial;
                } else {
                    $namaTampil = mb_substr($namaAsli, 0, 1) . '***';
                }
                $roleTampil = 'Orang Baik';
            }

            return [
                'id' => (string) $item->id,
                'nama' => $namaTampil,
                'role' => $roleTampil,
                'doa' => $item->pesan,
                'tanggal' => $item->created_at ? $item->created_at->diffForHumans() : 'Baru saja',
            ];
        });

        return response()->json($result);
    }
}
