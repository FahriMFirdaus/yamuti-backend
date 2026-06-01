<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\RoleRequest;
use App\Services\RoleService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    use ApiResponse;

    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index(): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();
        return $this->successResponse($roles, 'Data roles berhasil diambil');
    }

    public function show($id): JsonResponse
    {
        $role = $this->roleService->getRoleById($id);
        return $this->successResponse($role, 'Detail role berhasil diambil');
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $role = $this->roleService->createRole($request->validated());
        return $this->successResponse($role, 'Role berhasil ditambahkan', 201);
    }

    public function update(RoleRequest $request, $id): JsonResponse
    {
        $role = $this->roleService->updateRole($id, $request->validated());
        return $this->successResponse($role, 'Role berhasil diperbarui');
    }

    public function destroy($id): JsonResponse
    {
        $this->roleService->deleteRole($id);
        return $this->successResponse(null, 'Role berhasil dihapus');
    }
    
    public function permissions(): JsonResponse
    {
        $permissions = $this->roleService->getAllPermissions();
        return $this->successResponse($permissions, 'Data permissions berhasil diambil');
    }
}
