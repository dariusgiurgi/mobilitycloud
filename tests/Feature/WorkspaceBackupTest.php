<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\ParticipantAttachment;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceBackupService;
use App\Services\WorkspaceRestoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class WorkspaceBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_contains_structured_data_and_uploaded_files(): void
    {
        Storage::fake('local');
        $workspace = Workspace::create(['name' => 'Backup Workspace']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Backup Project',
            'status' => 'active',
        ]);
        $participant = Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Ana',
            'last_name' => 'Pop',
        ]);
        Storage::disk('local')->put('participants/consent.pdf', 'signed-consent');
        ParticipantAttachment::create([
            'participant_id' => $participant->id,
            'type' => 'gdpr',
            'path' => 'participants/consent.pdf',
            'disk' => 'local',
            'original_name' => 'consent.pdf',
            'size' => 14,
        ]);

        $path = app(WorkspaceBackupService::class)->create($workspace);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $payload = json_decode($zip->getFromName('workspace-data.json'), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Backup Workspace', $payload['workspace']['name']);
        $this->assertSame(2, $payload['format_version']);
        $this->assertSame('participant_attachment', $payload['file_index'][0]['entity']);
        $this->assertSame('Backup Project', $payload['projects'][0]['project']['name']);
        $this->assertSame('Ana', $payload['projects'][0]['participants'][0]['first_name']);
        $this->assertNotFalse($zip->locateName('files/'.$project->id.'-backup-project/participants/'.$participant->id.'-ana-pop/1-consent.pdf'));

        $zip->close();
        unlink($path);
    }

    public function test_backup_can_be_restored_non_destructively_with_files(): void
    {
        Storage::fake('local');
        $source = Workspace::create(['name' => 'Source Workspace']);
        $project = Project::create([
            'workspace_id' => $source->id,
            'name' => 'Restorable Project',
            'status' => 'active',
        ]);
        $participant = Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Mara',
            'last_name' => 'Ionescu',
        ]);
        Storage::disk('local')->put('source/passport.pdf', 'passport-copy');
        ParticipantAttachment::create([
            'participant_id' => $participant->id,
            'type' => 'id_copy',
            'path' => 'source/passport.pdf',
            'disk' => 'local',
            'original_name' => 'passport.pdf',
            'size' => 13,
        ]);
        $line = $project->budgetLines()->firstOrFail();
        $line->expenses()->create([
            'description' => 'Venue rental',
            'amount' => 500,
            'currency' => 'EUR',
            'exchange_rate' => 1,
            'amount_eur' => 500,
        ]);
        $archive = app(WorkspaceBackupService::class)->create($source);

        $target = Workspace::create(['name' => 'Target Workspace']);
        $admin = User::factory()->create();
        $target->users()->attach($admin, ['role' => 'admin']);
        $this->actingAs($admin);
        $result = app(WorkspaceRestoreService::class)->restore($target, $archive);

        $restored = $target->projects()->where('name', 'Restorable Project')->firstOrFail();
        $this->assertSame(1, $result['projects']);
        $this->assertSame(1, $result['files']);
        $this->assertSame('workspace', $restored->access_mode);
        $this->assertSame('Venue rental', $restored->budgetLines()->firstOrFail()->expenses()->firstOrFail()->description);
        $attachment = $restored->participants()->firstOrFail()->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertSame('passport-copy', Storage::disk('local')->get($attachment->path));

        unlink($archive);
    }

    public function test_only_workspace_admins_can_download_a_backup(): void
    {
        $workspace = Workspace::create(['name' => 'Protected Backup']);
        $viewer = User::factory()->create();
        $workspace->users()->attach($viewer, ['role' => 'viewer']);

        $this->actingAs($viewer)
            ->get(route('workspaces.backup', $workspace))
            ->assertForbidden();
    }

    public function test_account_backup_contains_account_visible_projects_without_workspace_membership(): void
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'workspace_id' => null,
            'name' => 'Account Owned Project',
            'status' => 'active',
        ]);
        $workspaceProject = Project::create([
            'workspace_id' => Workspace::create(['name' => 'Legacy Workspace'])->id,
            'name' => 'Hidden Legacy Project',
            'status' => 'active',
        ]);

        $this->actingAs($owner);

        $path = app(WorkspaceBackupService::class)->createForAccount(
            $owner,
            app(\App\Services\AccountWorkspaceService::class)->ensureFor($owner),
        );
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $payload = json_decode($zip->getFromName('workspace-data.json'), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Account backup - '.$owner->email, $payload['workspace']['name']);
        $this->assertContains($project->id, collect($payload['projects'])->pluck('project.id')->all());
        $this->assertNotContains($workspaceProject->id, collect($payload['projects'])->pluck('project.id')->all());

        $zip->close();
        unlink($path);
    }

    public function test_legacy_version_one_backup_remains_restorable(): void
    {
        Storage::fake('local');
        $source = Workspace::create(['name' => 'Legacy Source']);
        Project::create([
            'workspace_id' => $source->id,
            'name' => 'Legacy Project',
            'status' => 'writing',
        ]);
        $archive = app(WorkspaceBackupService::class)->create($source);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($archive) === true);
        $payload = json_decode($zip->getFromName('workspace-data.json'), true, flags: JSON_THROW_ON_ERROR);
        $payload['format_version'] = 1;
        unset($payload['file_index']);
        $zip->addFromString('workspace-data.json', json_encode($payload, JSON_THROW_ON_ERROR));
        $zip->close();

        $target = Workspace::create(['name' => 'Legacy Target']);
        $admin = User::factory()->create();
        $target->users()->attach($admin, ['role' => 'admin']);
        $this->actingAs($admin);

        $result = app(WorkspaceRestoreService::class)->restore($target, $archive);
        $this->assertSame(1, $result['projects']);
        $this->assertTrue($target->projects()->where('name', 'Legacy Project')->exists());
        unlink($archive);
    }
}
