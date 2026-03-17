<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@servidormp.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('B80885460'),
                'status' => 'active',
            ]
        );

        $user->name = 'Admin';
        $user->password = Hash::make('B80885460');
        $user->status = 'active';
        $user->save();

        if (! $user->hasRole('superadmin')) {
            $user->assignRole('superadmin');
        }

        // Quitar superadmin al usuario antiguo si existe
        $old = User::where('email', 'admin@mpadmin.local')->first();
        if ($old && $old->id !== $user->id) {
            $old->removeRole('superadmin');
        }
    }
}
