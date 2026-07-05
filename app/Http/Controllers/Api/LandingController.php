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
        $danaTersalurkan = Donasi::where('status', 'PAID')->sum('gross_amount');
        $programBerjalan = Kampanye::count();

        // Format dana tersalurkan agar ringkas (misal: "Rp 20 Juta+")
        $formattedDana = $this->formatRupiah($danaTersalurkan);

        // Jika data masih sedikit, kita bisa menambahkan fallback default agar tetap terlihat baik
        return response()->json([
            "jiwaterbantu" => ($jiwaTerbantu > 0 ? $jiwaTerbantu : "20") . "+",
            "danatersalurkan" => $danaTersalurkan > 0 ? $formattedDana . "+" : "Rp 20 Juta+",
            "programberjalan" => ($programBerjalan > 0 ? $programBerjalan : "20") . "+",
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
}
