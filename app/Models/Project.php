<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'public_url',
        'admin_url',
        'local_path',
        'project_type',
        'framework',
        'status',
        'icon_path',
        'color',
        'repository_url',
        'technical_notes',
        'has_mobile_app',
        'has_api',
        'main_endpoints',
        'backend_version',
        'mobile_app_version',
        'sync_status',
    ];

    protected $casts = [
        'has_mobile_app' => 'boolean',
        'has_api' => 'boolean',
        'main_endpoints' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->using(ProjectUser::class)
            ->withPivot('access_level')
            ->withTimestamps();
    }

    public function mobileIntegrations()
    {
        return $this->hasMany(MobileIntegration::class);
    }
}
