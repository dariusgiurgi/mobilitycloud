<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Participant;
use App\Models\ParticipantAttachment;
use App\Models\Project;
use App\Models\PublicContentBlock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_view_but_cannot_manage_a_project(): void
    {
        $project = $this->project();
        $viewer = User::factory()->create();
        $project->members()->attach($viewer, ['role' => Project::PROJECT_ROLE_VIEWER]);

        $this->assertTrue(Gate::forUser($viewer)->allows('view', $project));
        $this->assertFalse(Gate::forUser($viewer)->allows('update', $project));
        $this->assertFalse($project->canBeManagedBy($viewer));
    }

    public function test_project_editor_can_manage_a_project(): void
    {
        $project = $this->project();
        $editor = User::factory()->create();
        $project->members()->attach($editor, ['role' => Project::PROJECT_ROLE_EDITOR]);

        $this->assertTrue(Gate::forUser($editor)->allows('update', $project));
        $this->assertTrue($project->canBeManagedBy($editor));
    }

    public function test_participant_attachment_download_requires_project_access(): void
    {
        Storage::fake('local');
        $project = $this->project();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $project->members()->attach($member, ['role' => Project::PROJECT_ROLE_VIEWER]);

        $participant = Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Ana',
            'last_name' => 'Pop',
        ]);
        $path = 'participant-attachments/'.$participant->id.'/agreement_Ana_Pop.pdf';
        Storage::disk('local')->put($path, 'private-document');
        $attachment = ParticipantAttachment::create([
            'participant_id' => $participant->id,
            'type' => 'agreement',
            'path' => $path,
            'disk' => 'local',
            'original_name' => 'agreement.pdf',
            'size' => 16,
        ]);

        $this->actingAs($outsider)
            ->get(route('attachments.participants.download', $attachment))
            ->assertForbidden();

        $this->actingAs($member)
            ->get(route('attachments.participants.download', $attachment))
            ->assertOk()
            ->assertDownload('agreement.pdf');
    }

    public function test_deleting_a_participant_removes_its_private_files(): void
    {
        Storage::fake('local');
        $project = $this->project();
        $participant = Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Mara',
            'last_name' => 'Ionescu',
        ]);
        $path = 'participant-attachments/'.$participant->id.'/gdpr.pdf';
        Storage::disk('local')->put($path, 'gdpr');
        ParticipantAttachment::create([
            'participant_id' => $participant->id,
            'type' => 'gdpr',
            'path' => $path,
            'disk' => 'local',
            'size' => 4,
        ]);

        $participant->delete();

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('participant_attachments', ['participant_id' => $participant->id]);
    }

    public function test_force_deleting_project_removes_participant_and_expense_files(): void
    {
        Storage::fake('local');
        $project = $this->project();
        $participant = Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Dan',
            'last_name' => 'Popescu',
        ]);
        $participantPath = 'participant-attachments/'.$participant->id.'/id.pdf';
        Storage::disk('local')->put($participantPath, 'id');
        ParticipantAttachment::create([
            'participant_id' => $participant->id,
            'type' => 'id_copy',
            'path' => $participantPath,
            'disk' => 'local',
            'size' => 2,
        ]);

        $expensePath = 'expenses/receipt.pdf';
        Storage::disk('local')->put($expensePath, 'receipt');
        Expense::create([
            'budget_line_id' => $project->budgetLines()->firstOrFail()->id,
            'attachment_path' => $expensePath,
            'attachment_disk' => 'local',
        ]);

        $project->forceDelete();

        Storage::disk('local')->assertMissing($participantPath);
        Storage::disk('local')->assertMissing($expensePath);
    }

    public function test_moderator_can_persist_hidden_state_on_public_block(): void
    {
        $author = User::factory()->create();
        $block = PublicContentBlock::create([
            'user_id' => $author->id,
            'title' => 'Reported block',
            'body' => 'Content',
        ]);

        $block->update(['is_hidden' => true]);

        $this->assertTrue($block->fresh()->is_hidden);
    }

    private function project(): Project
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'workspace_id' => null,
            'name' => 'Test Project',
            'status' => 'writing',
        ]);

        return $project;
    }
}
