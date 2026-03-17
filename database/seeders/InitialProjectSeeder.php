<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class InitialProjectSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::firstOrCreate(
            ['slug' => 'proyecto-inicial'],
            [
                'name' => 'Proyecto Inicial',
                'description' => 'Proyecto real inicial administrado desde MP Admin VPS.',
                'public_url' => 'https://www.servidormp.com',
                'admin_url' => 'https://www.servidormp.com/admin',
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

        $super = User::where('email', 'admin@mpadmin.local')->first();
        if ($super) {
            $project->users()->syncWithoutDetaching([
                $super->id => ['access_level' => 'owner'],
            ]);
        }
    }
}
