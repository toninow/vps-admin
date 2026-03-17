<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'app_name',
        'platform',
        'current_version',
        'min_supported_version',
        'api_url',
        'integration_token',
        'connection_status',
        'last_synced_at',
        'notes',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'integration_token' => 'encrypted',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
