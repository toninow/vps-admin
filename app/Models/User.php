<?php

namespace App\Models;

use App\Support\MpsfpAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'avatar_path',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class)
            ->using(ProjectUser::class)
            ->withPivot('access_level')
            ->withTimestamps();
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function supplierImports()
    {
        return $this->hasMany(SupplierImport::class);
    }

    public function stockChanges()
    {
        return $this->hasMany(StockChange::class);
    }

    public function approvedMasterProducts()
    {
        return $this->hasMany(MasterProduct::class, 'approved_by_id');
    }

    public function resolvedEanIssues()
    {
        return $this->hasMany(ProductEanIssue::class, 'resolved_by_id');
    }

    public function appDevices()
    {
        return $this->hasMany(AppDevice::class);
    }

    public function userSessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canAccessMpsfp(): bool
    {
        return MpsfpAccess::canAccessModule($this);
    }

    public function canAccessMpsfpSection(string $section, string $ability = 'view'): bool
    {
        return MpsfpAccess::can($this, $section, $ability);
    }

    public function mpsfpCapabilities(): array
    {
        return MpsfpAccess::capabilities($this);
    }

    public function mpsfpPrimaryRole(): string
    {
        return MpsfpAccess::primaryRoleName($this);
    }
}
