<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PurgeUnverifiedUsers extends Command
{
    protected $signature = 'mobilitycloud:purge-unverified-users
        {--hours= : Delete regular accounts still unverified after this many hours}';

    protected $description = 'Permanently remove expired, unverified public registrations that have no project access';

    public function handle(): int
    {
        $hours = max(24, (int) ($this->option('hours') ?: config('mobilitycloud.registration.unverified_retention_hours', 48)));

        $users = User::query()
            ->where('role', User::ROLE_USER)
            ->whereNull('email_verified_at')
            ->where('created_at', '<=', now()->subHours($hours))
            ->whereDoesntHave('projects')
            ->whereDoesntHave('ownedProjects')
            ->get();

        $users->each->forceDelete();

        $this->info('Purged '.$users->count().' expired unverified registration(s).');

        return self::SUCCESS;
    }
}
