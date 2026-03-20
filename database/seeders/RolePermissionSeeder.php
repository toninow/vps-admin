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
            // Fase 2: proveedores e importaciones
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            'imports.view',
            'imports.create',
            'imports.process',
            'mappings.view',
            'mappings.edit',
            // productos
            'products.view',
            'products.edit',
            'master_products.view',
            'master_products.create',
            'master_products.edit',
            'master_products.approve',
            'master_products.delete',
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
            'ean.view',
            'ean.resolve',
            'duplicates.view',
            'duplicates.merge',
            'stock.view',
            'stock.edit',
            'export.view',
            'export.download',
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
                'logs.view', 'settings.view', 'settings.edit',
                'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
                'imports.view', 'imports.create', 'imports.process',
                'mappings.view', 'mappings.edit',
                'products.view', 'products.edit',
                'master_products.view', 'master_products.create', 'master_products.edit', 'master_products.approve', 'master_products.delete',
                'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
                'ean.view', 'ean.resolve',
                'duplicates.view', 'duplicates.merge',
                'stock.view', 'stock.edit',
                'export.view', 'export.download',
            ],
            'editor' => [
                'projects.view', 'projects.edit', 'projects.access',
                'mobile_integrations.view', 'mobile_integrations.edit',
                'suppliers.view', 'imports.view', 'imports.create', 'imports.process',
                'mappings.view', 'mappings.edit',
                'products.view', 'products.edit',
                'master_products.view', 'master_products.create', 'master_products.edit', 'master_products.approve',
                'categories.view', 'categories.create', 'categories.edit',
                'ean.view', 'ean.resolve',
                'duplicates.view', 'duplicates.merge',
                'stock.view', 'stock.edit',
                'export.view', 'export.download',
            ],
            'viewer' => [
                'projects.view', 'projects.access',
                'mobile_integrations.view',
                'suppliers.view', 'imports.view', 'mappings.view',
                'products.view', 'master_products.view', 'categories.view',
                'ean.view', 'duplicates.view', 'stock.view', 'export.view',
            ],
            'stock_user' => [
                'master_products.view', 'master_products.edit',
                'categories.view', 'categories.edit',
                'ean.view', 'ean.resolve',
                'stock.view', 'stock.edit',
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
