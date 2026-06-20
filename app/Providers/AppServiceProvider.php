<?php

namespace App\Providers;

use App\Models\ContentBlock;
use App\Models\Project;
use App\Policies\ContentBlockPolicy;
use App\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ContentBlock::class, ContentBlockPolicy::class);
    }
}
