<?php

namespace App\Providers;

use App\Http\Responses\Filament\UnifiedLoginResponse;
use App\Http\Responses\Filament\UnifiedLogoutResponse;
use App\Http\Responses\Filament\UnifiedRegistrationResponse;
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
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->app->bind(RegistrationResponse::class, UnifiedRegistrationResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('public-link-submissions', function (Request $request): Limit {
            $token = (string) $request->route('token');
            $key = hash('sha256', $token.'|'.$request->ip());

            return Limit::perMinute(max(30, (int) config('mobilitycloud.public_links.max_submissions_per_minute', 120)))
                ->by($key);
        });

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
