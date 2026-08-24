<?php

namespace App\Filament\Pages;

use App\Support\PlatformAudit;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
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
        return 'Live operational status, recent failures and safe remediation actions for the platform.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshHealth')
                ->label('Refresh')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(fn (): null => null),

            Action::make('restartQueue')
                ->label('Restart queue worker')
                ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('The worker will finish its current job and restart. Pending jobs remain queued.')
                ->action(function (): void {
                    $this->runCommand('queue:restart');

                    PlatformAudit::log(
                        'system_health.queue_restarted',
                        'Restarted the queue worker from System health.',
                    );

                    Notification::make()
                        ->title('Queue restart requested')
                        ->body('The queue worker will restart after the current job finishes.')
                        ->success()
                        ->send();
                }),

            Action::make('rebuildConfigCache')
                ->label('Rebuild config cache')
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Use this after changing server environment values. It refreshes Laravel configuration without changing application data.')
                ->action(function (): void {
                    $this->runCommand('config:clear');
                    $this->runCommand('config:cache');

                    PlatformAudit::log(
                        'system_health.config_cache_rebuilt',
                        'Rebuilt the Laravel configuration cache from System health.',
                    );

                    Notification::make()
                        ->title('Configuration cache rebuilt')
                        ->success()
                        ->send();
                }),

            Action::make('retryFailedJob')
                ->label('Retry failed job')
                ->icon(Heroicon::OutlinedPlay)
                ->color('danger')
                ->visible(fn (): bool => $this->failedJobsCount() > 0)
                ->requiresConfirmation()
                ->modalHeading('Retry a failed background job')
                ->modalDescription('The selected job will be placed back in the queue. It may send an email or perform another external action, depending on the job type.')
                ->form([
                    Select::make('uuid')
                        ->label('Failed job')
                        ->options(fn (): array => $this->failedJobOptions())
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $uuid = (string) ($data['uuid'] ?? '');

                    if ($uuid === '' || ! Schema::hasTable('failed_jobs') || ! DB::table('failed_jobs')->where('uuid', $uuid)->exists()) {
                        Notification::make()
                            ->title('Failed job is no longer available')
                            ->warning()
                            ->send();

                        return;
                    }

                    $this->runCommand('queue:retry', ['id' => $uuid]);

                    PlatformAudit::log(
                        'system_health.failed_job_retried',
                        'Retried a failed background job from System health.',
                        null,
                        ['job_uuid' => $uuid],
                    );

                    Notification::make()
                        ->title('Failed job queued again')
                        ->body('The queue worker will process it shortly.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function overview(): array
    {
        $checks = $this->checks();

        return [
            'checked_at' => now()->format('d M Y, H:i:s T'),
            'total' => count($checks),
            'ok' => collect($checks)->where('level', 'ok')->count(),
            'warn' => collect($checks)->where('level', 'warn')->count(),
            'bad' => collect($checks)->where('level', 'bad')->count(),
            'queued_jobs' => $this->queuedJobsCount(),
            'failed_jobs' => $this->failedJobsCount(),
            'storage' => $this->storageUsage(),
        ];
    }

    public function checks(): array
    {
        return [
            $this->databaseCheck(),
            $this->cacheCheck(),
            $this->configurationCacheCheck(),
            $this->storageCheck(),
            $this->queueCheck(),
            $this->failedJobsCheck(),
            $this->mailCheck(),
            $this->backupCheck(),
            $this->applicationLogCheck(),
            $this->schedulerCheck(),
            $this->environmentCheck(),
        ];
    }

    public function failedJobs(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return [];
        }

        try {
            return DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(8)
                ->get()
                ->map(function (object $job): array {
                    $payload = json_decode((string) $job->payload, true) ?: [];
                    $failedAt = filled($job->failed_at)
                        ? CarbonImmutable::parse($job->failed_at)->format('d M Y, H:i:s T')
                        : 'Unknown time';

                    return [
                        'uuid' => (string) $job->uuid,
                        'queue' => (string) $job->connection.'@'.(string) $job->queue,
                        'name' => (string) ($payload['displayName'] ?? 'Unknown job'),
                        'failed_at' => $failedAt,
                        'error' => $this->errorSummary((string) $job->exception),
                    ];
                })
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    protected function databaseCheck(): array
    {
        try {
            DB::select('select 1');

            return $this->ok('Database', 'Connection available', 'Driver: '.config('database.default'));
        } catch (Throwable $exception) {
            return $this->bad('Database', 'Connection failed', $this->errorSummary($exception->getMessage()));
        }
    }

    protected function cacheCheck(): array
    {
        try {
            Cache::put('platform_health_check', now()->toISOString(), 10);

            return $this->ok('Cache', 'Read/write available', 'Store: '.config('cache.default'));
        } catch (Throwable $exception) {
            return $this->bad('Cache', 'Cache write failed', $this->errorSummary($exception->getMessage()));
        }
    }

    protected function configurationCacheCheck(): array
    {
        $path = base_path('bootstrap/cache/config.php');

        if (! File::exists($path)) {
            return app()->isProduction()
                ? $this->warn('Configuration cache', 'Not cached', 'Use “Rebuild config cache” after confirming server environment values.')
                : $this->ok('Configuration cache', 'Not cached', 'Normal for local development.');
        }

        $updatedAt = CarbonImmutable::createFromTimestamp(File::lastModified($path));

        return $this->ok('Configuration cache', 'Cached', 'Last rebuilt '.$updatedAt->diffForHumans().' · '.$updatedAt->format('d M Y, H:i:s T'));
    }

    protected function storageCheck(): array
    {
        $path = storage_path('app/.platform-health-check');

        try {
            File::put($path, 'ok');
            File::delete($path);

            $usage = $this->storageUsage();
            $detail = $usage === null
                ? 'Local storage is writable: '.storage_path('app')
                : 'Local storage is writable · '.$usage['free'].' free of '.$usage['total'].' ('.$usage['used_percent'].' used)';

            return $this->ok('Storage', 'Local storage is writable', $detail);
        } catch (Throwable $exception) {
            return $this->bad('Storage', 'Local storage is not writable', $this->errorSummary($exception->getMessage()));
        }
    }

    protected function queueCheck(): array
    {
        $connection = (string) config('queue.default');

        if ($connection === 'sync') {
            return $this->warn('Queue', 'Using sync queue', 'Good for local/dev. Production should use database or redis with a worker.');
        }

        if (! Schema::hasTable('jobs')) {
            return $this->warn('Queue', 'Connection configured', 'Queue: '.$connection.' · pending job count is unavailable.');
        }

        try {
            $count = $this->queuedJobsCount();
            $oldest = DB::table('jobs')->min('created_at');
            $detail = 'Queue: '.$connection.' · '.$count.' pending job(s)';

            if ($count > 0 && filled($oldest)) {
                $detail .= ' · oldest queued '.CarbonImmutable::createFromTimestamp((int) $oldest)->diffForHumans();
            }

            return $count > 50
                ? $this->warn('Queue', 'Backlog needs review', $detail)
                : $this->ok('Queue', 'Queue connection configured', $detail);
        } catch (Throwable $exception) {
            return $this->bad('Queue', 'Queue status unavailable', $this->errorSummary($exception->getMessage()));
        }
    }

    protected function failedJobsCheck(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return $this->warn('Failed jobs', 'Table missing', 'Run migrations before production use.');
        }

        try {
            $count = $this->failedJobsCount();
            $latest = collect($this->failedJobs())->first();
            $detail = $count === 0
                ? 'Queue failures table is clean.'
                : 'Review the details below and retry only after the underlying issue is fixed.';

            if ($latest) {
                $detail .= ' Latest: '.$latest['name'].' · '.$latest['failed_at'];
            }

            return $count > 0
                ? $this->warn('Failed jobs', $count.' failed job(s)', $detail)
                : $this->ok('Failed jobs', 'No failed jobs', $detail);
        } catch (Throwable $exception) {
            return $this->bad('Failed jobs', 'Could not read failures', $this->errorSummary($exception->getMessage()));
        }
    }

    protected function mailCheck(): array
    {
        $mailer = (string) config('mail.default');
        $smtp = (array) config('mail.mailers.smtp', []);
        $host = (string) ($smtp['host'] ?? '');
        $port = (string) ($smtp['port'] ?? '');
        $from = (string) config('mail.from.address');

        if ($mailer === 'log' || ($mailer === 'smtp' && blank($host))) {
            return $this->warn('Mail', 'Production SMTP not configured', 'Current mailer: '.$mailer);
        }

        if ($mailer === 'smtp' && blank($smtp['password'] ?? null)) {
            return $this->bad('Mail', 'SMTP password missing', 'Add the SMTP password in the server environment, then rebuild the configuration cache.');
        }

        return $this->ok('Mail', 'Mailer configured', $mailer.($host !== '' ? ' · '.$host.($port !== '' ? ':'.$port : '') : '').' · sender: '.$from);
    }

    protected function backupCheck(): array
    {
        $statusPath = (string) config('mobilitycloud.backups.status_path');

        if ($statusPath === '' || ! File::exists($statusPath)) {
            return $this->warn('Backups', 'No backup status record', 'The next scheduled or manual backup will publish safe status metadata here.');
        }

        try {
            $status = json_decode((string) File::get($statusPath), true, flags: JSON_THROW_ON_ERROR);

            if (($status['status'] ?? null) !== 'ok') {
                $recordedAt = filled($status['recorded_at'] ?? null)
                    ? CarbonImmutable::parse($status['recorded_at'])->diffForHumans()
                    : 'at an unknown time';

                return $this->bad(
                    'Backups',
                    'Latest backup failed',
                    $recordedAt.' · '.$this->errorSummary((string) ($status['error'] ?? 'No failure detail was recorded.')),
                );
            }

            $lastBackup = CarbonImmutable::parse($status['created_at']);
            $maxAgeHours = max(1, (int) config('mobilitycloud.backups.max_age_hours', 30));
            $files = collect($status['files'] ?? []);
            $detail = $files->count().' file(s) · '.$this->formatBytes((int) ($status['total_size_bytes'] ?? 0)).' · '.$lastBackup->diffForHumans();

            return $lastBackup->isBefore(now()->subHours($maxAgeHours))
                ? $this->warn('Backups', 'Latest backup is older than expected', $detail)
                : $this->ok('Backups', 'Recent backup available', $detail);
        } catch (Throwable $exception) {
            return $this->bad('Backups', 'Backup status could not be read', $this->errorSummary($exception->getMessage()));
        }
    }

    protected function applicationLogCheck(): array
    {
        $path = storage_path('logs/laravel.log');
        $latest = $this->latestLogError($path);

        if ($latest === null) {
            return $this->ok('Application log', 'No recent error found', 'Scanned the latest entries in '.basename($path).'.');
        }

        $loggedAt = CarbonImmutable::parse($latest['at']);
        $detail = $loggedAt->diffForHumans().' · '.$latest['message'];

        return $loggedAt->isAfter(now()->subDay())
            ? $this->warn('Application log', 'Recent error recorded', $detail)
            : $this->warn('Application log', 'Historical error recorded', $detail);
    }

    protected function schedulerCheck(): array
    {
        $consoleRoutes = base_path('routes/console.php');
        $taskCount = File::exists($consoleRoutes)
            ? substr_count((string) File::get($consoleRoutes), 'Schedule::command(')
            : 0;

        return $taskCount > 0
            ? $this->ok('Scheduler', $taskCount.' scheduled command(s) registered', 'Ensure cron runs artisan schedule:run every minute.')
            : $this->warn('Scheduler', 'No scheduled commands detected', 'Add scheduler checks when recurring production jobs are introduced.');
    }

    protected function environmentCheck(): array
    {
        return app()->isProduction()
            ? $this->ok('Environment', 'Production mode', 'APP_ENV=production · PHP '.PHP_VERSION)
            : $this->warn('Environment', 'Non-production mode', 'APP_ENV='.app()->environment().' · PHP '.PHP_VERSION);
    }

    protected function failedJobsCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    protected function queuedJobsCount(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        return (int) DB::table('jobs')->count();
    }

    protected function failedJobOptions(): array
    {
        return collect($this->failedJobs())
            ->mapWithKeys(fn (array $job): array => [
                $job['uuid'] => $job['name'].' · '.$job['failed_at'],
            ])
            ->all();
    }

    protected function storageUsage(): ?array
    {
        $path = storage_path();
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return null;
        }

        $used = $total - $free;

        return [
            'total' => $this->formatBytes($total),
            'free' => $this->formatBytes($free),
            'used_percent' => number_format(($used / $total) * 100, 1).'%',
        ];
    }

    protected function latestLogError(string $path): ?array
    {
        if (! File::exists($path) || File::size($path) === 0) {
            return null;
        }

        $handle = fopen($path, 'rb');

        if (! $handle) {
            return null;
        }

        try {
            $size = File::size($path);
            fseek($handle, max(0, $size - 262144));
            $tail = stream_get_contents($handle) ?: '';
        } finally {
            fclose($handle);
        }

        foreach (array_reverse(preg_split('/\R/', $tail) ?: []) as $line) {
            if (! preg_match('/^\[(?<at>[^\]]+)\]\s+[^.]+\.ERROR:\s+(?<message>.+)$/', $line, $matches)) {
                continue;
            }

            try {
                CarbonImmutable::parse($matches['at']);
            } catch (Throwable) {
                continue;
            }

            return [
                'at' => $matches['at'],
                'message' => $this->errorSummary($matches['message']),
            ];
        }

        return null;
    }

    protected function errorSummary(string $message): string
    {
        $firstLine = trim((string) (preg_split('/\R/', $message)[0] ?? $message));
        $redacted = preg_replace('/(?i)(password|secret|token)\s*(?:=|:|=>)\s*[^\s,}]+/', '$1=[redacted]', $firstLine) ?? $firstLine;

        return Str::limit($redacted, 320);
    }

    protected function formatBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = max(0, (float) $bytes);
        $unit = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return number_format($value, $unit === 0 ? 0 : 1).' '.$units[$unit];
    }

    protected function runCommand(string $command, array $parameters = []): void
    {
        try {
            $exitCode = Artisan::call($command, $parameters);
        } catch (Throwable $exception) {
            throw new RuntimeException($this->errorSummary($exception->getMessage()), previous: $exception);
        }

        if ($exitCode !== 0) {
            throw new RuntimeException($this->errorSummary(Artisan::output()) ?: 'The '.$command.' command failed.');
        }
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
