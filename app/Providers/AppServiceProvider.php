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
use Filament\Support\Components\ComponentManager;
use Filament\Tables\Table;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

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
        if ($this->app->environment('local')) {
            \Illuminate\Support\Facades\View::composer('filament-tables::index', function (View $view): void {
                $data = $view->getData();
                if (! array_key_exists('getColumnManagerApplyAction', $data)) {
                    $table = $data['table'] ?? null;
                    logger()->error('Missing Filament table view callback', [
                        'view' => $view->name(),
                        'data_keys' => array_keys($data),
                        'table_class' => is_object($table) ? $table::class : get_debug_type($table),
                        'method_exists' => is_object($table) && method_exists($table, 'getColumnManagerApplyAction'),
                        'extracted_callback_exists' => $table instanceof Table
                            && array_key_exists('getColumnManagerApplyAction', ComponentManager::resolve()->extractPublicMethods($table)),
                    ]);
                }
            });
        }

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
