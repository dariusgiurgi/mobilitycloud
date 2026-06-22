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
use App\Observers\ProjectActivityObserver;
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

        foreach ([
            Project::class,
            ProjectApplicationSection::class,
            BudgetLine::class,
            BudgetTransfer::class,
            Expense::class,
            Participant::class,
            ProjectDocument::class,
        ] as $model) {
            $model::observe(ProjectActivityObserver::class);
        }
    }
}
