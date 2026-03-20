<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Project::class => \App\Policies\ProjectPolicy::class,
        \App\Models\ActivityLog::class => \App\Policies\ActivityLogPolicy::class,
        \App\Models\MobileIntegration::class => \App\Policies\MobileIntegrationPolicy::class,
        \App\Models\Supplier::class => \App\Policies\SupplierPolicy::class,
        \App\Models\SupplierImport::class => \App\Policies\SupplierImportPolicy::class,
        \App\Models\NormalizedProduct::class => \App\Policies\NormalizedProductPolicy::class,
        \App\Models\MasterProduct::class => \App\Policies\MasterProductPolicy::class,
        \App\Models\Category::class => \App\Policies\CategoryPolicy::class,
        \App\Models\ProductEanIssue::class => \App\Policies\ProductEanIssuePolicy::class,
        \App\Models\DuplicateProductGroup::class => \App\Policies\DuplicateProductGroupPolicy::class,
        \App\Models\Setting::class => \App\Policies\SettingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
                return true;
            }

            return null;
        });
    }
}
