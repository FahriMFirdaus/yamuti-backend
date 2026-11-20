<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        // Ambil semua user staf pengurus (kecuali peran donatur/guest biasa)
        $admins = User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['donatur', 'guest']);
        })->with('roles')->get();

        return $this->successResponse($admins, 'Daftar admin berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'no_hp' => 'nullable|string|max:20',
            'nik' => 'nullable|string|max:20',
            'skck' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'status_pegawai' => 'nullable|string|max:50',
            'role' => 'required|string|exists:roles,name',
            'foto_identitas' => 'nullable|image|max:5120',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        if ($request->hasFile('foto_identitas')) {
            $path = $request->file('foto_identitas')->store('identitas/users', 's3');
            $validated['foto_identitas'] = Storage::disk('s3')->url($path);
        }

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
            'no_hp' => 'nullable|string|max:20',
            'nik' => 'nullable|string|max:20',
            'skck' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'status_pegawai' => 'nullable|string|max:50',
            'role' => 'sometimes|string|exists:roles,name',
            'foto_identitas' => 'nullable|image|max:5120',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        if ($request->hasFile('foto_identitas')) {
            $path = $request->file('foto_identitas')->store('identitas/users', 's3');
            $validated['foto_identitas'] = Storage::disk('s3')->url($path);
        }

        $admin->update($validated);

        if ($request->has('role')) {
            $admin->syncRoles([$request->role]);
        }

        return $this->successResponse($admin->load('roles'), 'Data admin berhasil diperbarui');
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $admin = User::findOrFail($id);

        // Cegah menghapus akun sendiri
        if ($admin->id === $request->user()?->id) {
            return $this->errorResponse('Anda tidak dapat menghapus akun Anda sendiri.', 422);
        }

        // Cegah menghapus super admin terakhir
        if ($admin->hasRole('super_admin')) {
            $superAdminCount = User::role('super_admin')->count();
            if ($superAdminCount <= 1) {
                return $this->errorResponse('Akun Super Admin terakhir tidak boleh dihapus untuk mencegah penguncian sistem.', 422);
            }
        }

        $admin->delete();

        return $this->successResponse(null, 'Akses admin berhasil dihapus');
    }
}
