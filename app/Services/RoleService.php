<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleService extends BaseService
{
    public function getAllRoles()
    {
        return Role::with('permissions')->get();
    }

    public function getRoleById($id)
    {
        return Role::with('permissions')->findOrFail($id);
    }

    public function createRole(array $data)
    {
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->load('permissions');
    }

    public function updateRole($id, array $data)
    {
        $role = Role::findOrFail($id);
        $role->update(['name' => $data['name']]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->load('permissions');
    }

    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);
        return $role->delete();
    }
    
    public function getAllPermissions()
    {
        return Permission::all();
    }
}
