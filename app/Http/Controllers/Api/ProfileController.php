<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        return $this->successResponse($request->user()->load('roles.permissions'), 'Data profil berhasil diambil');
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,'.$user->id,
            'no_hp' => 'sometimes|string|min:9|max:15',
            'foto_identitas' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('foto_identitas')) {
            $path = $request->file('foto_identitas')->store('identitas/users', 's3');
            $validated['foto_identitas'] = Storage::disk('s3')->url($path);
        }

        $oldEmail = $user->email;
        $oldNoHp = $user->no_hp;

        $user->update($validated);

        $emailChanged = isset($validated['email']) && $validated['email'] !== $oldEmail;
        $noHpChanged = isset($validated['no_hp']) && $validated['no_hp'] !== $oldNoHp;

        if ($emailChanged && $oldNoHp) {
            $pesan = "⚠️ *Peringatan Keamanan YAMUTI*\n\nEmail akun Anda baru saja diubah menjadi *".$user->email.'*. Jika ini bukan Anda, segera amankan akun Anda atau hubungi Admin Yayasan.';
            SendWhatsAppMessage::dispatch($oldNoHp, $pesan);
        }

        if ($noHpChanged && $oldNoHp) {
            $pesan = "⚠️ *Peringatan Keamanan YAMUTI*\n\nNomor WhatsApp akun Anda baru saja diubah menjadi *".$user->no_hp.'*. Jika ini bukan Anda, segera amankan akun Anda atau hubungi Admin Yayasan.';
            SendWhatsAppMessage::dispatch($oldNoHp, $pesan);
        }

        return $this->successResponse($user->load('roles'), 'Profil berhasil diperbarui');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return $this->successResponse(null, 'Kata sandi berhasil diperbarui');
    }

    public function kontakUtama(): JsonResponse
    {
        // Cari pengguna dengan role super_admin, prioritaskan yang punya nomor HP
        $admin = User::role('super_admin')->whereNotNull('no_hp')->first();

        if (! $admin) {
            $admin = User::role('super_admin')->first();
        }

        // Default fallback
        $noWa = $admin && $admin->no_hp ? $admin->no_hp : '6281234567890';

        // Hapus karakter non-digit (seperti spasi, strip, atau plus)
        $noWa = preg_replace('/[^0-9]/', '', $noWa);

        // Jika diawali dengan angka 0, ubah menjadi 62 agar format wa.me valid
        if (str_starts_with($noWa, '0')) {
            $noWa = '62'.substr($noWa, 1);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kontak utama berhasil diambil',
            'data' => [
                'no_whatsapp' => $noWa,
                'nama_admin' => $admin ? $admin->name : 'Admin YAMUTI',
            ],
        ]);
    }
}
