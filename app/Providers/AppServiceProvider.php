<?php

namespace App\Providers;

use App\Models\BudgetLine;
use App\Models\BudgetTransfer;
use App\Models\ContentBlock;
use App\Models\Expense;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\ProjectDocument;
use App\Models\ProjectTask;
use App\Observers\ProjectActivityObserver;
use App\Policies\ContentBlockPolicy;
use App\Policies\ProjectPolicy;
use App\Http\Responses\Filament\UnifiedLoginResponse;
use App\Http\Responses\Filament\UnifiedLogoutResponse;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponse::class, UnifiedLoginResponse::class);
        $this->app->bind(LogoutResponse::class, UnifiedLogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(ContentBlock::class, ContentBlockPolicy::class);

        foreach ([
            Project::class,
            ProjectApplicationSection::class,
            BudgetLine::class,
            BudgetTransfer::class,
            Expense::class,
            Participant::class,
            ProjectDocument::class,
            ProjectTask::class,
        ] as $model) {
            $model::observe(ProjectActivityObserver::class);
        }
    }
}
