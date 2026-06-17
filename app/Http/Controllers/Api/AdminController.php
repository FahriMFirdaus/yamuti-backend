<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        // Hanya ambil user dengan role tertentu atau semua admin
        $admins = User::with('roles')->get();
        return $this->successResponse($admins, 'Daftar admin berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'no_whatsapp' => 'nullable|string|max:20',
            'role' => 'required|string|exists:roles,name'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $admin = User::create($validated);
        $admin->assignRole($request->role);

        return $this->successResponse($admin, 'Admin baru berhasil ditambahkan', 201);
    }

    public function show($id): JsonResponse
    {
        $admin = User::with('roles')->findOrFail($id);
        return $this->successResponse($admin, 'Detail admin berhasil diambil');
    }

    public function update(Request $request, $id): JsonResponse
    {
        $admin = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($admin->id),
            ],
            'password' => 'sometimes|string|min:8',
            'no_whatsapp' => 'nullable|string|max:20',
            'role' => 'sometimes|string|exists:roles,name'
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $admin->update($validated);

        if ($request->has('role')) {
            $admin->syncRoles([$request->role]);
        }

        return $this->successResponse($admin->load('roles'), 'Data admin berhasil diperbarui');
    }

    public function destroy($id): JsonResponse
    {
        $admin = User::findOrFail($id);
        $admin->delete();

        return $this->successResponse(null, 'Akses admin berhasil dihapus');
    }
}
