<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AnakAsuh;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateAlumniStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anak-asuh:update-alumni';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengecek dan mengubah status anak asuh menjadi Alumni jika usianya melewati 18 tahun';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pengecekan umur Anak Asuh...');

        // Ambang batas umur alumni (misal 18 tahun)
        $batasUmur = 18;
        $tanggalBatas = Carbon::now()->subYears($batasUmur);

        // Cari anak asuh yang masih aktif, tapi tanggal lahirnya sebelum/sama dengan tanggal batas
        $anakAsuhLulus = AnakAsuh::where('status', '!=', 'Alumni')
            ->whereDate('tanggal_lahir', '<=', $tanggalBatas)
            ->get();

        if ($anakAsuhLulus->isEmpty()) {
            $this->info('Tidak ada Anak Asuh yang bertransisi menjadi Alumni hari ini.');
            return;
        }

        $count = 0;
        foreach ($anakAsuhLulus as $anak) {
            $anak->update(['status' => 'Alumni']);
            $count++;
            
            Log::info("Status anak asuh {$anak->nama} ({$anak->id}) diubah menjadi Alumni secara otomatis.");
        }

        $this->info("Berhasil memperbarui status {$count} Anak Asuh menjadi Alumni.");
    }
}
