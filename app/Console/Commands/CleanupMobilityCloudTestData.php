<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class CleanupMobilityCloudTestData extends Command
{
    protected $signature = 'mobilitycloud:cleanup-test-data
        {--execute : Actually delete candidates; without this the command only reports}
        {--force : Required with --execute in production}';

    protected $description = 'Find obvious test/demo records before launch and optionally delete them';

    public function handle(): int
    {
        if ($this->option('execute') && app()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to delete production data without --force.');

            return self::FAILURE;
        }

        try {
            DB::connection()->getPdo();

            $projectIds = $this->testProjects()->pluck('id');
            $invitationIds = $this->testInvitations()->pluck('id');
            $userIds = $this->testUsers()->pluck('id');
        } catch (Throwable $exception) {
            $this->error('Database unavailable: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Record type', 'Candidates'], [
            ['Projects', $projectIds->count()],
            ['Invitations', $invitationIds->count()],
            ['Regular users', $userIds->count()],
        ]);

        if (! $this->option('execute')) {
            $this->warn('Dry-run only. Re-run with --execute --force after you approve the candidate list.');
            $this->line('Project IDs: '.$projectIds->take(30)->implode(', '));
            $this->line('User IDs: '.$userIds->take(30)->implode(', '));

            return self::SUCCESS;
        }

        DB::transaction(function () use ($projectIds, $invitationIds, $userIds): void {
            ProjectInvitation::query()->whereKey($invitationIds)->delete();

            Project::withTrashed()
                ->whereKey($projectIds)
                ->get()
                ->each
                ->forceDelete();

            User::withTrashed()
                ->whereKey($userIds)
                ->get()
                ->each
                ->forceDelete();
        });

        $this->info('Test/demo candidate data deleted.');

        return self::SUCCESS;
    }

    private function testProjects(): Builder
    {
        return Project::withTrashed()
            ->where(function (Builder $query): void {
                foreach (['test', 'demo', 'qa', 'xtp', 'roots in motion'] as $term) {
                    $query->orWhereRaw('LOWER(name) LIKE ?', ['%'.$term.'%'])
                        ->orWhereRaw('LOWER(acronym) LIKE ?', ['%'.$term.'%']);
                }
            });
    }

    private function testInvitations(): Builder
    {
        return ProjectInvitation::query()
            ->where(function (Builder $query): void {
                foreach (['test', 'demo', 'qa', 'example.com', 'codex'] as $term) {
                    $query->orWhereRaw('LOWER(email) LIKE ?', ['%'.$term.'%']);
                }
            });
    }

    private function testUsers(): Builder
    {
        $protectedEmails = array_filter([
            config('mobilitycloud.emails.owner'),
            config('mail.from.address'),
            'contact@mobilitycloud.eu',
            'darius@mobilitycloud.eu',
        ]);

        return User::withTrashed()
            ->where('role', User::ROLE_USER)
            ->whereNotIn('email', $protectedEmails)
            ->where(function (Builder $query): void {
                foreach (['test', 'demo', 'qa', 'example.com', 'codex'] as $term) {
                    $query->orWhereRaw('LOWER(email) LIKE ?', ['%'.$term.'%'])
                        ->orWhereRaw('LOWER(name) LIKE ?', ['%'.$term.'%']);
                }
            });
    }
}
