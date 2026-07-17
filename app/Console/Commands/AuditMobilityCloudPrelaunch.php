<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class AuditMobilityCloudPrelaunch extends Command
{
    protected $signature = 'mobilitycloud:prelaunch-audit';

    protected $description = 'Run launch-readiness checks for production operations';

    public function handle(): int
    {
        $failed = 0;

        $failed += $this->check('APP_ENV is production outside local/dev', app()->isLocal() || app()->environment('testing') || app()->isProduction());
        $failed += $this->check('APP_DEBUG is disabled outside local/dev', app()->isLocal() || app()->environment('testing') || config('app.debug') === false);
        $failed += $this->check('APP_URL uses HTTPS', app()->isLocal() || str_starts_with((string) config('app.url'), 'https://'));
        $databaseWorks = $this->databaseWorks();

        $failed += $this->check('Database connection works', $databaseWorks);
        $failed += $this->check('Mail transport is configured', filled(config('mail.mailers.'.config('mail.default').'.host')) || config('mail.default') === 'log');
        $failed += $this->check('Support email is configured', filled(config('mobilitycloud.emails.support')));

        if ($databaseWorks) {
            $failed += $this->check('At least one platform owner exists', User::query()->where('role', User::ROLE_PLATFORM_OWNER)->exists());
            $failed += $this->check('No overdue project payments require immediate action', Project::query()->where('invoice_status', Project::INVOICE_OVERDUE)->doesntExist(), warnOnly: true);
        } else {
            $failed += $this->check('At least one platform owner exists', false);
            $this->components->warn('Skipped payment checks because the database is unavailable.');
        }
        $failed += $this->check('Recent backup exists', $this->recentBackupExists(), warnOnly: true);

        if ($failed > 0) {
            $this->error($failed.' required launch checks failed.');

            return self::FAILURE;
        }

        $this->info('Required prelaunch checks passed.');

        return self::SUCCESS;
    }

    private function check(string $label, bool $passes, bool $warnOnly = false): int
    {
        if ($passes) {
            $this->components->info($label);

            return 0;
        }

        if ($warnOnly) {
            $this->components->warn($label);

            return 0;
        }

        $this->components->error($label);

        return 1;
    }

    private function databaseWorks(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function recentBackupExists(): bool
    {
        $backupPath = rtrim((string) config('mobilitycloud.backups.path'), '/');
        $maxAgeHours = (int) config('mobilitycloud.backups.max_age_hours', 30);

        if (! is_dir($backupPath)) {
            return false;
        }

        $latest = collect(File::glob($backupPath.'/db-*.sql.gz'))
            ->sortByDesc(fn (string $file): int => filemtime($file) ?: 0)
            ->first();

        return $latest && (filemtime($latest) ?: 0) >= now()->subHours($maxAgeHours)->getTimestamp();
    }
}
