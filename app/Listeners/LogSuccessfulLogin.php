<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => Request::ip(),
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'description' => 'Inicio de sesión exitoso.',
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
