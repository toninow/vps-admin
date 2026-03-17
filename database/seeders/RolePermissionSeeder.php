<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'projects.view',
            'projects.create',
            'projects.edit',
            'projects.delete',
            'projects.access',
            'mobile_integrations.view',
            'mobile_integrations.edit',
            'logs.view',
            'settings.view',
            'settings.edit',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm, 'guard_name' => 'web']
            );
        }

        $roles = [
            'superadmin' => $permissions,
            'admin' => [
                'users.view', 'users.create', 'users.edit',
                'projects.view', 'projects.create', 'projects.edit', 'projects.access',
                'mobile_integrations.view', 'mobile_integrations.edit',
                'logs.view', 'settings.view',
            ],
            'editor' => [
                'projects.view', 'projects.edit', 'projects.access',
                'mobile_integrations.view', 'mobile_integrations.edit',
            ],
            'viewer' => [
                'projects.view', 'projects.access',
                'mobile_integrations.view',
            ],
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );
            $role->syncPermissions($perms);
        }
    }
}
