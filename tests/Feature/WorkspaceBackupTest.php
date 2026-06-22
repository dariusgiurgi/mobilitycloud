<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\ParticipantAttachment;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceBackupService;
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
        $this->assertSame('Backup Project', $payload['projects'][0]['project']['name']);
        $this->assertSame('Ana', $payload['projects'][0]['participants'][0]['first_name']);
        $this->assertNotFalse($zip->locateName('files/'.$project->id.'-backup-project/participants/'.$participant->id.'-ana-pop/1-consent.pdf'));

        $zip->close();
        unlink($path);
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
}
