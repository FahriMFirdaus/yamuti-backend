<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService extends BaseService
{
    public function register(array $data): array
    {
        $fotoUrl = null;
        if (isset($data['foto_identitas'])) {
            $path = $data['foto_identitas']->store('identitas/users', 's3');
            $fotoUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url($path);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'nik' => $data['nik'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
            'skck' => $data['skck'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'status_pegawai' => $data['status_pegawai'] ?? 'Aktif',
            'foto_identitas' => $fotoUrl,
        ]);

        // Assign default role untuk user yang register secara publik
        if (\Spatie\Permission\Models\Role::where('name', 'donatur')->exists()) {
            $user->assignRole('donatur');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password yang Anda masukkan salah.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        // Mencabut token yang sedang digunakan saat ini
        $user->currentAccessToken()->delete();
    }
}
