<?php

namespace App\Support;

use App\Models\User;

class MpsfpAccess
{
    public const ROLE_ORDER = [
        'superadmin',
        'admin',
        'editor',
        'viewer',
        'stock_user',
    ];

    public const ROLE_LABELS = [
        'superadmin' => 'Superadministrador',
        'admin' => 'Administrador',
        'editor' => 'Editor',
        'viewer' => 'Consulta',
        'stock_user' => 'Stock',
    ];

    public const SECTIONS = [
        'proveedores' => [
            'label' => 'Proveedores',
            'description' => 'Alta, revisión y mapeo de proveedores activos del sistema.',
            'permissions' => [
                'view' => 'suppliers.view',
                'create' => 'suppliers.create',
                'edit' => 'suppliers.edit',
            ],
        ],
        'importaciones' => [
            'label' => 'Importaciones',
            'description' => 'Subida de archivos, preview, mapeo y procesamiento de catálogos.',
            'permissions' => [
                'view' => 'imports.view',
                'create' => 'imports.create',
                'process' => 'imports.process',
            ],
        ],
        'normalizados' => [
            'label' => 'Normalizados',
            'description' => 'Productos procesados desde proveedores antes de consolidarlos en catálogo maestro.',
            'permissions' => [
                'view' => 'products.view',
                'edit' => 'products.edit',
            ],
        ],
        'maestros' => [
            'label' => 'Maestros',
            'description' => 'Catálogo maestro operativo, aprobación de productos y base de exportación.',
            'permissions' => [
                'view' => 'master_products.view',
                'create' => 'master_products.create',
                'edit' => 'master_products.edit',
                'approve' => 'master_products.approve',
                'delete' => 'master_products.delete',
            ],
        ],
        'ean' => [
            'label' => 'Incidencias EAN',
            'description' => 'Seguimiento de EAN vacíos, inválidos y pendientes de corrección.',
            'permissions' => [
                'view' => 'ean.view',
                'resolve' => 'ean.resolve',
            ],
        ],
        'duplicados' => [
            'label' => 'Duplicados',
            'description' => 'Agrupación y revisión de productos con el mismo EAN o conflicto de identidad.',
            'permissions' => [
                'view' => 'duplicates.view',
                'merge' => 'duplicates.merge',
            ],
        ],
        'cruce_proveedores' => [
            'label' => 'Cruce proveedores',
            'description' => 'Productos con el mismo EAN detectados en varios proveedores.',
            'permissions' => [
                'view' => 'duplicates.view',
            ],
        ],
        'categorias' => [
            'label' => 'Categorías',
            'description' => 'Árbol maestro y estructura final de clasificación para exportación.',
            'permissions' => [
                'view' => 'categories.view',
                'create' => 'categories.create',
                'edit' => 'categories.edit',
            ],
        ],
        'exportacion' => [
            'label' => 'Exportación',
            'description' => 'Preparación del catálogo maestro para exportación final a PrestaShop.',
            'permissions' => [
                'view' => 'export.view',
                'download' => 'export.download',
            ],
        ],
        'configuracion' => [
            'label' => 'Configuración',
            'description' => 'Ajustes del entorno, negocio e integración del módulo.',
            'permissions' => [
                'view' => 'settings.view',
                'edit' => 'settings.edit',
            ],
        ],
    ];

    public static function canAccessModule(User $user): bool
    {
        if (self::isSuperadmin($user)) {
            return true;
        }

        foreach (self::SECTIONS as $section => $meta) {
            if (self::can($user, $section, 'view')) {
                return true;
            }
        }

        return false;
    }

    public static function can(User $user, string $section, string $ability = 'view'): bool
    {
        if (self::isSuperadmin($user)) {
            return true;
        }

        $sectionMeta = self::SECTIONS[$section] ?? null;
        if (! $sectionMeta) {
            return false;
        }

        $permission = $sectionMeta['permissions'][$ability] ?? null;
        if (! $permission && $ability !== 'view') {
            $permission = $sectionMeta['permissions']['view'] ?? null;
        }

        return $permission ? $user->can($permission) : false;
    }

    public static function capabilities(User $user): array
    {
        $sections = [];

        foreach (self::SECTIONS as $key => $meta) {
            $actions = [];
            foreach ($meta['permissions'] as $ability => $permission) {
                $actions[$ability] = self::can($user, $key, $ability);
            }

            $view = $actions['view'] ?? false;
            $manageAbilities = array_diff(array_keys($actions), ['view']);
            $canManage = false;
            foreach ($manageAbilities as $ability) {
                if (! empty($actions[$ability])) {
                    $canManage = true;
                    break;
                }
            }

            $mode = $canManage ? 'manage' : ($view ? 'view' : 'blocked');

            $sections[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'description' => $meta['description'],
                'actions' => $actions,
                'view' => $view,
                'manage' => $canManage,
                'mode' => $mode,
                'mode_label' => self::modeLabel($mode),
                'reason' => self::reasonForSection($meta, $view),
            ];
        }

        return [
            'role' => self::primaryRoleName($user),
            'role_label' => self::roleLabel(self::primaryRoleName($user)),
            'sections' => $sections,
            'counts' => [
                'manage' => count(array_filter($sections, fn (array $section) => $section['mode'] === 'manage')),
                'view' => count(array_filter($sections, fn (array $section) => $section['mode'] === 'view')),
                'blocked' => count(array_filter($sections, fn (array $section) => $section['mode'] === 'blocked')),
            ],
        ];
    }

    public static function primaryRoleName(User $user): string
    {
        foreach (self::ROLE_ORDER as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return $user->getRoleNames()->first() ?? 'sin_rol';
    }

    public static function roleLabel(string $role): string
    {
        return self::ROLE_LABELS[$role] ?? ucfirst(str_replace('_', ' ', $role));
    }

    protected static function isSuperadmin(User $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('superadmin');
    }

    protected static function modeLabel(string $mode): string
    {
        return match ($mode) {
            'manage' => 'Gestionar',
            'view' => 'Consultar',
            default => 'Sin acceso',
        };
    }

    protected static function reasonForSection(array $meta, bool $view): string
    {
        if ($view) {
            return 'Disponible para tu rol actual.';
        }

        $permission = $meta['permissions']['view'] ?? null;

        return $permission
            ? 'Requiere el permiso ' . $permission . '.'
            : 'Esta sección todavía no tiene una política de acceso definida.';
    }
}
