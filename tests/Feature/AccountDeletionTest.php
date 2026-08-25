<?php

namespace Tests\Feature;

use App\Filament\Resources\PlatformUsers\Pages\ListPlatformUsers;
use App\Jobs\DeletePlatformAccount;
use App\Models\PlatformAuditLog;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Services\AccountDeletionService;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_action_blocks_account_and_queues_an_audited_explicit_project_disposition(): void
    {
        Queue::fake();

        $actor = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $account = User::factory()->create(['email' => 'queued@example.test']);
        $this->project($account, 'Project to purge');

        $this->actingAs($actor);
        Filament::setCurrentPanel('platform');

        Livewire::test(ListPlatformUsers::class)
            ->callTableAction('deletePermanently', $account, data: [
                'project_disposition' => AccountDeletionService::PROJECTS_PURGE,
                'purge_confirmation' => 'PURGE OWNED PROJECTS',
                'confirmation_email' => $account->email,
            ]);

        $account->refresh();
        $this->assertTrue($account->is_suspended);
        $this->assertNotNull($account->archived_at);
        $this->assertSame(User::ACCOUNT_DELETION_QUEUED, $account->account_deletion_status);
        $this->assertSame(AccountDeletionService::PROJECTS_PURGE, $account->account_deletion_project_disposition);
        $this->assertDatabaseHas('platform_audit_logs', [
            'actor_id' => $actor->id,
            'subject_id' => $account->id,
            'action' => 'account.deletion_requested',
        ]);
        Queue::assertPushed(DeletePlatformAccount::class, fn (DeletePlatformAccount $job): bool => $job->accountId === $account->id
            && $job->projectDisposition === AccountDeletionService::PROJECTS_PURGE
            && $job->transferAccountId === null);

        Livewire::test(ListPlatformUsers::class)
            ->assertTableActionHidden('restore', $account)
            ->assertTableActionHidden('deletePermanently', $account);
    }

    public function test_owned_projects_are_transferred_before_account_deletion(): void
    {
        Storage::fake('local');

        $actor = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $account = User::factory()->create(['email' => 'departing@example.test']);
        $recipient = User::factory()->create(['email' => 'recipient@example.test']);
        $project = $this->project($account, 'Active project');
        $archivedProject = $this->project($account, 'Archived project');
        $archivedProject->delete();

        $grantPath = 'project-approval-proofs/'.$project->id.'/grant.pdf';
        Storage::disk('local')->put($grantPath, 'grant proof');
        $project->update([
            'approved_grant_proof_path' => $grantPath,
            'approved_grant_proof_disk' => 'local',
        ]);

        $job = new DeletePlatformAccount(
            $account->id,
            AccountDeletionService::PROJECTS_TRANSFER,
            $recipient->id,
            $actor->id,
            $account->email,
        );
        $job->handle(app(AccountDeletionService::class));

        $this->assertNull(User::withTrashed()->find($account->id));
        $this->assertSame($recipient->id, $project->fresh()->owner_id);
        $this->assertSame($recipient->id, Project::withTrashed()->findOrFail($archivedProject->id)->owner_id);
        $this->assertTrue(Project::withTrashed()->findOrFail($archivedProject->id)->trashed());
        Storage::disk('local')->assertExists($grantPath);

        $audit = PlatformAuditLog::query()->where('action', 'account.deleted_permanently')->firstOrFail();
        $this->assertSame(AccountDeletionService::PROJECTS_TRANSFER, $audit->metadata['project_disposition']);
        $this->assertSame(2, $audit->metadata['transferred_projects']);
        $this->assertSame($recipient->id, $audit->metadata['transfer_account_id']);
    }

    public function test_project_purge_removes_owned_records_and_all_private_files_without_touching_shared_projects(): void
    {
        Storage::fake('local');

        $actor = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $account = User::factory()->create(['email' => 'purged@example.test']);
        $otherOwner = User::factory()->create();
        $ownedProject = $this->project($account, 'Owned project');
        $sharedProject = $this->project($otherOwner, 'Shared project');
        $sharedProject->members()->attach($account, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $grantPath = 'project-approval-proofs/'.$ownedProject->id.'/grant.pdf';
        $documentPath = 'project-documents/'.$ownedProject->id.'/evidence.pdf';
        $logoPath = 'account-branding/'.$account->id.'/logo.png';
        Storage::disk('local')->put($grantPath, 'grant proof');
        Storage::disk('local')->put($documentPath, 'evidence');
        Storage::disk('local')->put($logoPath, 'logo');

        $ownedProject->update([
            'approved_grant_proof_path' => $grantPath,
            'approved_grant_proof_disk' => 'local',
        ]);
        ProjectDocument::create([
            'project_id' => $ownedProject->id,
            'type' => ProjectDocument::TYPE_UPLOAD,
            'category' => 'other',
            'title' => 'Evidence',
            'file_path' => $documentPath,
            'file_disk' => 'local',
            'file_name' => 'evidence.pdf',
            'file_size' => 8,
        ]);
        $account->update(['document_settings' => ['logo_path' => $logoPath]]);

        $result = app(AccountDeletionService::class)->execute(
            $account->id,
            AccountDeletionService::PROJECTS_PURGE,
            null,
            $actor->id,
            $account->email,
        );

        $this->assertSame(1, $result['purged_projects']);
        $this->assertNull(User::withTrashed()->find($account->id));
        $this->assertNull(Project::withTrashed()->find($ownedProject->id));
        $this->assertNotNull(Project::find($sharedProject->id));
        $this->assertDatabaseMissing('project_user', [
            'project_id' => $sharedProject->id,
            'user_id' => $account->id,
        ]);
        Storage::disk('local')->assertMissing($grantPath);
        Storage::disk('local')->assertMissing($documentPath);
        Storage::disk('local')->assertMissing($logoPath);
    }

    public function test_transfer_requires_a_different_active_customer_account(): void
    {
        $actor = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $account = User::factory()->create();
        $project = $this->project($account, 'Protected project');
        $suspendedRecipient = User::factory()->create(['is_suspended' => true]);

        try {
            app(AccountDeletionService::class)->execute(
                $account->id,
                AccountDeletionService::PROJECTS_TRANSFER,
                $suspendedRecipient->id,
                $actor->id,
                $account->email,
            );

            $this->fail('An inactive transfer account should be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('The project transfer account must be an active customer account.', $exception->getMessage());
        }

        $this->assertNotNull(User::find($account->id));
        $this->assertSame($account->id, $project->fresh()->owner_id);
    }

    public function test_transfer_rejects_an_account_with_its_own_deletion_request(): void
    {
        $actor = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $account = User::factory()->create();
        $project = $this->project($account, 'Protected project');
        $recipient = User::factory()->create([
            'account_deletion_status' => User::ACCOUNT_DELETION_QUEUED,
            'account_deletion_project_disposition' => AccountDeletionService::PROJECTS_PURGE,
        ]);

        try {
            app(AccountDeletionService::class)->execute(
                $account->id,
                AccountDeletionService::PROJECTS_TRANSFER,
                $recipient->id,
                $actor->id,
                $account->email,
            );

            $this->fail('A transfer target pending deletion should be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('The project transfer account must be an active customer account.', $exception->getMessage());
        }

        $this->assertNotNull(User::find($account->id));
        $this->assertSame($account->id, $project->fresh()->owner_id);
    }

    public function test_a_suspended_owner_cannot_authorise_deleting_the_last_active_platform_owner(): void
    {
        $suspendedActor = User::factory()->create([
            'role' => User::ROLE_PLATFORM_OWNER,
            'is_suspended' => true,
        ]);
        $lastActiveOwner = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);

        try {
            app(AccountDeletionService::class)->execute(
                $lastActiveOwner->id,
                AccountDeletionService::PROJECTS_PURGE,
                null,
                $suspendedActor->id,
                $lastActiveOwner->email,
            );

            $this->fail('A suspended owner must not authorise permanent account deletion.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Only an active platform owner can permanently delete accounts.', $exception->getMessage());
        }

        $this->assertNotNull(User::find($lastActiveOwner->id));
    }

    public function test_direct_force_delete_cannot_leave_owned_projects_orphaned(): void
    {
        $account = User::factory()->create();
        $project = $this->project($account, 'Must keep an owner');

        try {
            $account->forceDelete();
            $this->fail('Direct account deletion should be blocked while projects are still owned.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Owned projects must be transferred or purged before permanently deleting an account.',
                $exception->getMessage(),
            );
        }

        $this->assertNotNull(User::find($account->id));
        $this->assertSame($account->id, $project->fresh()->owner_id);
    }

    public function test_database_constraint_rejects_user_deletion_while_projects_are_owned(): void
    {
        $account = User::factory()->create();
        $project = $this->project($account, 'Database protected owner');

        try {
            DB::table('users')->where('id', $account->id)->delete();
            $this->fail('The database must reject deletion of a project owner.');
        } catch (QueryException) {
            // The foreign key is the final guard when model events are bypassed.
        }

        $this->assertNotNull(User::find($account->id));
        $this->assertSame($account->id, $project->fresh()->owner_id);
    }

    public function test_completed_deletion_job_is_safe_to_run_again(): void
    {
        $actor = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $account = User::factory()->create();
        $service = app(AccountDeletionService::class);

        $service->execute(
            $account->id,
            AccountDeletionService::PROJECTS_PURGE,
            null,
            $actor->id,
            $account->email,
        );

        $secondResult = $service->execute(
            $account->id,
            AccountDeletionService::PROJECTS_PURGE,
            null,
            $actor->id,
            $account->email,
        );

        $this->assertSame([
            'owned_projects' => 0,
            'transferred_projects' => 0,
            'purged_projects' => 0,
        ], $secondResult);
        $this->assertSame(1, PlatformAuditLog::query()->where('action', 'account.deleted_permanently')->count());
    }

    public function test_job_rejects_parameters_that_do_not_match_the_persisted_request(): void
    {
        $actor = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $account = User::factory()->create([
            'account_deletion_status' => User::ACCOUNT_DELETION_QUEUED,
            'account_deletion_requested_by' => $actor->id,
            'account_deletion_project_disposition' => AccountDeletionService::PROJECTS_PURGE,
        ]);
        $recipient = User::factory()->create();
        $job = new DeletePlatformAccount(
            $account->id,
            AccountDeletionService::PROJECTS_TRANSFER,
            $recipient->id,
            $actor->id,
            $account->email,
        );

        try {
            $job->handle(app(AccountDeletionService::class));
            $this->fail('Changed deletion parameters must be rejected.');
        } catch (LogicException $exception) {
            $this->assertSame('The queued account deletion does not match the immutable deletion request.', $exception->getMessage());
        }

        $this->assertNotNull($account->fresh());
        $this->assertSame(User::ACCOUNT_DELETION_QUEUED, $account->fresh()->account_deletion_status);
    }

    public function test_failed_deletion_is_visible_and_can_retry_only_the_original_request(): void
    {
        Queue::fake();
        $actor = User::factory()->create(['role' => User::ROLE_PLATFORM_OWNER]);
        $account = User::factory()->create([
            'email' => 'retry@example.test',
            'archived_at' => now(),
            'is_suspended' => true,
            'account_deletion_status' => User::ACCOUNT_DELETION_PROCESSING,
            'account_deletion_requested_by' => $actor->id,
            'account_deletion_project_disposition' => AccountDeletionService::PROJECTS_PURGE,
        ]);
        $job = new DeletePlatformAccount(
            $account->id,
            AccountDeletionService::PROJECTS_PURGE,
            null,
            $actor->id,
            $account->email,
        );
        $job->failed(new \RuntimeException('Storage temporarily unavailable.'));

        $this->assertSame(User::ACCOUNT_DELETION_FAILED, $account->fresh()->account_deletion_status);
        $this->assertStringContainsString('Storage temporarily unavailable', $account->fresh()->account_deletion_failure);

        $this->actingAs($actor);
        Filament::setCurrentPanel('platform');

        Livewire::test(ListPlatformUsers::class)
            ->assertTableActionVisible('retryPermanentDeletion', $account->fresh())
            ->assertTableActionHidden('restore', $account->fresh())
            ->callTableAction('retryPermanentDeletion', $account->fresh());

        $this->assertSame(User::ACCOUNT_DELETION_QUEUED, $account->fresh()->account_deletion_status);
        $this->assertNull($account->fresh()->account_deletion_failure);
        Queue::assertPushed(DeletePlatformAccount::class, fn (DeletePlatformAccount $queued): bool => $queued->accountId === $account->id
            && $queued->projectDisposition === AccountDeletionService::PROJECTS_PURGE
            && $queued->transferAccountId === null);
    }

    private function project(User $owner, string $name): Project
    {
        return Project::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'status' => 'writing',
        ]);
    }
}
