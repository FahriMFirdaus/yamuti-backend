<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

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
            'no_hp' => 'sometimes|string|max:20',
            'foto_identitas' => 'nullable|image|max:5120'
        ]);

        if ($request->hasFile('foto_identitas')) {
            $path = $request->file('foto_identitas')->store('identitas/users', 's3');
            $validated['foto_identitas'] = \Illuminate\Support\Facades\Storage::disk('s3')->url($path);
        }

        $user->update($validated);

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
}
