<?php

namespace App\Services;

use App\Models\PlatformAuditLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class AccountDeletionService
{
    public const PROJECTS_TRANSFER = 'transfer';

    public const PROJECTS_PURGE = 'purge';

    public function __construct(
        private readonly StoredFilePurgeService $files,
    ) {}

    /**
     * Permanently delete an account without ever leaving owned projects behind.
     *
     * Project purging deliberately happens one project at a time. The file purge
     * service verifies that private evidence is gone before the database cascades
     * the remaining records, and a queue retry can safely continue with projects
     * that are still present after a partial infrastructure failure.
     *
     * @return array{owned_projects:int, transferred_projects:int, purged_projects:int}
     */
    public function execute(
        int $accountId,
        string $projectDisposition,
        ?int $transferAccountId,
        int $actorId,
        string $accountEmail,
    ): array {
        $account = User::withTrashed()->find($accountId);

        // A completed job is a successful no-op when delivered again.
        if (! $account) {
            return [
                'owned_projects' => 0,
                'transferred_projects' => 0,
                'purged_projects' => 0,
            ];
        }

        $actor = User::query()->find($actorId);
        $this->assertDeletionAllowed($account, $actor);

        if (! in_array($projectDisposition, [self::PROJECTS_TRANSFER, self::PROJECTS_PURGE], true)) {
            throw new InvalidArgumentException('Every account deletion must explicitly transfer or purge its owned projects.');
        }

        $ownedProjectCount = Project::withTrashed()->where('owner_id', $account->id)->count();
        $transferredProjectCount = 0;
        $purgedProjectCount = 0;

        if ($projectDisposition === self::PROJECTS_TRANSFER) {
            if (! $transferAccountId || $transferAccountId === $account->id) {
                throw new InvalidArgumentException('Choose a different active customer account for project transfer.');
            }

            $transferredProjectCount = DB::transaction(function () use ($account, $transferAccountId): int {
                // Always lock both accounts in ID order so overlapping deletion
                // and transfer requests cannot deadlock or observe stale state.
                $accounts = User::withTrashed()
                    ->whereIn('id', [$account->id, $transferAccountId])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $lockedAccount = $accounts->get($account->id);
                $transferAccount = $accounts->get($transferAccountId);

                if (! $lockedAccount) {
                    return 0;
                }

                $this->assertValidTransferAccount($lockedAccount, $transferAccount);

                return Project::withTrashed()
                    ->where('owner_id', $lockedAccount->id)
                    ->update(['owner_id' => $transferAccount->id]);
            }, 3);
        } else {
            while ($project = Project::withTrashed()
                ->where('owner_id', $account->id)
                ->orderBy('id')
                ->first()) {
                $this->files->purgeProject($project);

                if (! $project->forceDelete()) {
                    throw new RuntimeException('An owned project could not be permanently deleted.');
                }

                $purgedProjectCount++;
            }
        }

        if (Project::withTrashed()->where('owner_id', $account->id)->exists()) {
            throw new RuntimeException('The account still owns projects and cannot be deleted.');
        }

        $this->files->purgeAccountBranding($account);

        DB::transaction(function () use (
            $account,
            $actor,
            $accountEmail,
            $projectDisposition,
            $transferAccountId,
            $ownedProjectCount,
            $transferredProjectCount,
            $purgedProjectCount,
        ): void {
            $lockedAccount = User::withTrashed()->whereKey($account->id)->lockForUpdate()->first();

            if (! $lockedAccount) {
                return;
            }

            if (Project::withTrashed()->where('owner_id', $lockedAccount->id)->exists()) {
                throw new RuntimeException('The account acquired a project while deletion was running.');
            }

            PlatformAuditLog::create([
                'actor_id' => $actor->id,
                'subject_type' => User::class,
                'subject_id' => $lockedAccount->id,
                'action' => 'account.deleted_permanently',
                'description' => 'Permanently deleted account '.$accountEmail,
                'metadata' => [
                    'deleted_user_id' => $lockedAccount->id,
                    'project_disposition' => $projectDisposition,
                    'transfer_account_id' => $transferAccountId,
                    'owned_projects_at_start' => $ownedProjectCount,
                    'transferred_projects' => $transferredProjectCount,
                    'purged_projects' => $purgedProjectCount,
                ],
            ]);

            if (! $lockedAccount->forceDelete()) {
                throw new RuntimeException('The account could not be permanently deleted.');
            }
        });

        return [
            'owned_projects' => $ownedProjectCount,
            'transferred_projects' => $transferredProjectCount,
            'purged_projects' => $purgedProjectCount,
        ];
    }

    private function assertDeletionAllowed(User $account, ?User $actor): void
    {
        if (! $actor?->canManagePlatformAdmins() || filled($actor->archived_at) || $actor->is_suspended) {
            throw new RuntimeException('Only an active platform owner can permanently delete accounts.');
        }

        if ($account->is($actor)) {
            throw new RuntimeException('A platform owner cannot permanently delete their own account.');
        }

        if ($account->isPlatformOwner()) {
            $anotherActiveOwnerExists = User::query()
                ->whereKeyNot($account->id)
                ->whereNull('archived_at')
                ->where('is_suspended', false)
                ->whereNull('account_deletion_status')
                ->whereIn('role', [User::ROLE_PLATFORM_OWNER, User::ROLE_ADMIN])
                ->exists();

            if (! $anotherActiveOwnerExists) {
                throw new RuntimeException('The last active platform owner cannot be permanently deleted.');
            }
        }
    }

    private function assertValidTransferAccount(User $account, ?User $transferAccount): void
    {
        if (! $transferAccount || $transferAccount->id === $account->id) {
            throw new InvalidArgumentException('Choose a different active customer account for project transfer.');
        }

        if ($transferAccount->trashed()
            || $transferAccount->role !== User::ROLE_USER
            || filled($transferAccount->archived_at)
            || $transferAccount->is_suspended
            || filled($transferAccount->account_deletion_status)) {
            throw new InvalidArgumentException('The project transfer account must be an active customer account.');
        }
    }
}
