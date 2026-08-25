<?php

namespace App\Jobs;

use App\Models\PlatformAuditLog;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

class DeletePlatformAccount implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 900;

    public int $uniqueFor = 14400;

    public function __construct(
        public int $accountId,
        public string $projectDisposition,
        public ?int $transferAccountId,
        public int $actorId,
        public string $accountEmail,
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return 'platform-account-deletion:'.$this->accountId;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [15, 60, 180, 300];
    }

    public function handle(AccountDeletionService $deletions): void
    {
        $account = DB::transaction(function (): ?User {
            $account = User::withTrashed()->whereKey($this->accountId)->lockForUpdate()->first();

            if (! $account) {
                return null;
            }

            if (filled($account->account_deletion_status)) {
                $matchesRequest = $account->account_deletion_project_disposition === $this->projectDisposition
                    && (int) ($account->account_deletion_requested_by ?? 0) === $this->actorId
                    && (int) ($account->account_deletion_transfer_account_id ?? 0) === (int) ($this->transferAccountId ?? 0);

                if (! $matchesRequest) {
                    throw new LogicException('The queued account deletion does not match the immutable deletion request.');
                }
            }

            $account->forceFill([
                'account_deletion_status' => User::ACCOUNT_DELETION_PROCESSING,
                'account_deletion_requested_at' => $account->account_deletion_requested_at ?: now(),
                'account_deletion_requested_by' => $account->account_deletion_requested_by ?: $this->actorId,
                'account_deletion_project_disposition' => $account->account_deletion_project_disposition ?: $this->projectDisposition,
                'account_deletion_transfer_account_id' => $account->account_deletion_transfer_account_id ?: $this->transferAccountId,
                'account_deletion_started_at' => $account->account_deletion_started_at ?: now(),
                'account_deletion_failure' => null,
            ])->save();

            return $account;
        });

        if (! $account) {
            return;
        }

        $deletions->execute(
            $this->accountId,
            $this->projectDisposition,
            $this->transferAccountId,
            $this->actorId,
            $this->accountEmail,
        );
    }

    public function failed(?Throwable $exception): void
    {
        User::withTrashed()->whereKey($this->accountId)->update([
            'account_deletion_status' => User::ACCOUNT_DELETION_FAILED,
            'account_deletion_failure' => Str::limit($exception?->getMessage() ?: 'Unknown account deletion failure.', 2000, ''),
        ]);

        PlatformAuditLog::create([
            'actor_id' => User::query()->whereKey($this->actorId)->value('id'),
            'subject_type' => User::class,
            'subject_id' => $this->accountId,
            'action' => 'account.deletion_failed',
            'description' => 'Could not permanently delete account '.$this->accountEmail,
            'metadata' => [
                'deleted_user_id' => $this->accountId,
                'project_disposition' => $this->projectDisposition,
                'transfer_account_id' => $this->transferAccountId,
                'exception' => $exception ? $exception::class : null,
                'message' => Str::limit($exception?->getMessage() ?: '', 1000, ''),
            ],
        ]);
    }
}
