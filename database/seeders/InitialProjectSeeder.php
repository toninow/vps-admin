<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class InitialProjectSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::updateOrCreate(
            ['slug' => 'proyecto-inicial'],
            [
                'name' => 'Proyecto Inicial',
                'description' => 'Proyecto real inicial administrado desde MP Admin VPS.',
                'public_url' => 'https://www.servidormp.com',
                // En este despliegue el backend admin está servido bajo / (y /mp-admin-vps redirige a /).
                'admin_url' => 'https://www.servidormp.com',
                'local_path' => '/var/www/html',
                'project_type' => 'web',
                'framework' => 'Laravel',
                'status' => 'active',
                'color' => '#E6007E',
                'has_mobile_app' => true,
                'has_api' => true,
                'main_endpoints' => [
                    'health' => '/api/health',
                    'login' => '/api/auth/login',
                ],
                'backend_version' => '1.0.0',
                'mobile_app_version' => '1.0.0',
                'sync_status' => 'synced',
            ]
        );

        // MPSFP (Musical Princesa Sistema Formato Proveedores)
        $mpsfp = Project::updateOrCreate(
            ['slug' => 'mpsfp'],
            [
                'name' => 'MPSFP',
                'description' => 'Musical Princesa Sistema Formato Proveedores (MPSFP).',
                // La SPA MPSFP debe estar servida por el vhost en /mpsfp/.
                'public_url' => 'https://www.servidormp.com/mpsfp',
                'admin_url' => 'https://www.servidormp.com',
                'local_path' => '/var/www/html',
                'project_type' => 'mpsfp',
                'framework' => 'MusicalPrincesa',
                'status' => 'active',
                'icon_path' => null,
                'color' => '#E6007E',
                'has_mobile_app' => false,
                'has_api' => true,
                'main_endpoints' => [
                    'imports' => '/mpsfp/imports',
                    'results' => '/mpsfp/results',
                    'upload' => '/mpsfp/upload',
                    'categories' => '/mpsfp/categories',
                ],
                'backend_version' => '1.0.0',
                'mobile_app_version' => null,
                'sync_status' => 'synced',
            ]
        );

        $super = User::where('email', 'admin@mpadmin.local')->first();
        if ($super) {
            // Asignar acceso del superadmin al proyecto inicial y al MPSFP
            $project->users()->syncWithoutDetaching([
                $super->id => ['access_level' => 'owner'],
            ]);

            $mpsfp->users()->syncWithoutDetaching([
                $super->id => ['access_level' => 'owner'],
            ]);
        }
    }
}
