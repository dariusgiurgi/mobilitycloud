<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PlatformHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static string|\UnitEnum|null $navigationGroup = 'Audit & operations';

    protected static ?string $navigationLabel = 'System health';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'System health';

    protected string $view = 'filament.pages.platform-health';

    public static function canAccess(): bool
    {
        return auth()->user()?->isPlatformAdmin() ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Quick operational checks for storage, database, queue, scheduler and mail configuration.';
    }

    public function checks(): array
    {
        return [
            $this->databaseCheck(),
            $this->cacheCheck(),
            $this->storageCheck(),
            $this->queueCheck(),
            $this->failedJobsCheck(),
            $this->mailCheck(),
            $this->schedulerCheck(),
            $this->environmentCheck(),
        ];
    }

    protected function databaseCheck(): array
    {
        try {
            DB::select('select 1');

            return $this->ok('Database', 'Connection available', config('database.default'));
        } catch (Throwable $exception) {
            return $this->bad('Database', 'Connection failed', $exception->getMessage());
        }
    }

    protected function cacheCheck(): array
    {
        try {
            Cache::put('platform_health_check', now()->toISOString(), 10);

            return $this->ok('Cache', 'Read/write available', config('cache.default'));
        } catch (Throwable $exception) {
            return $this->bad('Cache', 'Cache write failed', $exception->getMessage());
        }
    }

    protected function storageCheck(): array
    {
        $path = storage_path('app/.platform-health-check');

        try {
            File::put($path, 'ok');
            File::delete($path);

            return $this->ok('Storage', 'Local storage is writable', storage_path('app'));
        } catch (Throwable $exception) {
            return $this->bad('Storage', 'Local storage is not writable', $exception->getMessage());
        }
    }

    protected function queueCheck(): array
    {
        $connection = config('queue.default');

        return $connection === 'sync'
            ? $this->warn('Queue', 'Using sync queue', 'Good for local/dev. For production, use database/redis and a worker.')
            : $this->ok('Queue', 'Queue connection configured', (string) $connection);
    }

    protected function failedJobsCheck(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return $this->warn('Failed jobs', 'Table missing', 'Run migrations before production use.');
        }

        $count = DB::table('failed_jobs')->count();

        return $count > 0
            ? $this->warn('Failed jobs', $count.' failed job(s)', 'Review and retry/flush failed jobs.')
            : $this->ok('Failed jobs', 'No failed jobs', 'Queue failures table is clean.');
    }

    protected function mailCheck(): array
    {
        $mailer = (string) config('mail.default');
        $host = (string) config('mail.mailers.smtp.host');

        if ($mailer === 'log' || blank($host)) {
            return $this->warn('Mail', 'Production SMTP not configured', 'Current mailer: '.$mailer);
        }

        return $this->ok('Mail', 'Mailer configured', $mailer.' · '.$host);
    }

    protected function schedulerCheck(): array
    {
        $consoleRoutes = base_path('routes/console.php');
        $hasCommands = File::exists($consoleRoutes) && str_contains(File::get($consoleRoutes), 'Schedule::');

        return $hasCommands
            ? $this->ok('Scheduler', 'Scheduled commands registered', 'Ensure cron runs artisan schedule:run every minute.')
            : $this->warn('Scheduler', 'No scheduled commands detected', 'Add scheduler checks when recurring production jobs are introduced.');
    }

    protected function environmentCheck(): array
    {
        return app()->isProduction()
            ? $this->ok('Environment', 'Production mode', 'APP_ENV=production')
            : $this->warn('Environment', 'Non-production mode', 'APP_ENV='.app()->environment());
    }

    protected function ok(string $label, string $status, string $detail): array
    {
        return compact('label', 'status', 'detail') + ['level' => 'ok'];
    }

    protected function warn(string $label, string $status, string $detail): array
    {
        return compact('label', 'status', 'detail') + ['level' => 'warn'];
    }

    protected function bad(string $label, string $status, string $detail): array
    {
        return compact('label', 'status', 'detail') + ['level' => 'bad'];
    }
}
