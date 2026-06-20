<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_member_can_download_landscape_attendance_pdf(): void
    {
        [$workspace, $project] = $this->workspaceAndProject();
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);
        $this->participants($project);
        $document = $this->attendanceDocument($project);

        $response = $this->actingAs($user)
            ->get(route('project-documents.attendance', [$project, $document]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('841.890 595.280', $response->getContent());
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()));
    }

    public function test_outsider_cannot_download_attendance_pdf(): void
    {
        [, $project] = $this->workspaceAndProject();
        $document = $this->attendanceDocument($project);

        $this->actingAs(User::factory()->create())
            ->get(route('project-documents.attendance', [$project, $document]))
            ->assertForbidden();
    }

    public function test_signed_copy_is_private_and_requires_membership(): void
    {
        Storage::fake('local');
        [$workspace, $project] = $this->workspaceAndProject();
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace->users()->attach($member, ['role' => 'viewer']);
        $document = $this->attendanceDocument($project);
        $path = 'project-documents/'.$project->id.'/'.$document->id.'/signed.pdf';
        Storage::disk('local')->put($path, 'signed');
        $document->update([
            'signed_path' => $path,
            'signed_disk' => 'local',
            'signed_name' => 'signed-attendance.pdf',
            'signed_size' => 6,
            'signed_at' => now(),
        ]);

        $this->actingAs($outsider)
            ->get(route('project-documents.signed', [$project, $document]))
            ->assertForbidden();

        $this->actingAs($member)
            ->get(route('project-documents.signed', [$project, $document]))
            ->assertOk()
            ->assertDownload('signed-attendance.pdf');
    }

    public function test_deleting_document_removes_signed_file(): void
    {
        Storage::fake('local');
        [, $project] = $this->workspaceAndProject();
        $document = $this->attendanceDocument($project);
        $path = 'project-documents/'.$project->id.'/'.$document->id.'/signed.pdf';
        Storage::disk('local')->put($path, 'signed');
        $document->update(['signed_path' => $path, 'signed_disk' => 'local']);

        $document->delete();

        Storage::disk('local')->assertMissing($path);
    }

    private function workspaceAndProject(): array
    {
        $workspace = Workspace::create(['name' => 'Documents Workspace']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'acronym' => 'YE',
            'status' => 'active',
        ]);

        return [$workspace, $project];
    }

    private function attendanceDocument(Project $project): ProjectDocument
    {
        return ProjectDocument::create([
            'project_id' => $project->id,
            'type' => ProjectDocument::TYPE_ATTENDANCE,
            'title' => 'Attendance list - Workshop',
            'activity_title' => 'Workshop',
            'activity_date' => '2026-06-20',
            'location' => 'Bucharest',
            'generated_at' => now(),
        ]);
    }

    private function participants(Project $project): void
    {
        foreach ([
            ['first_name' => 'Zoe', 'last_name' => 'Adams', 'partner_organisation' => 'Association B'],
            ['first_name' => 'Ana', 'last_name' => 'Pop', 'partner_organisation' => 'Association A'],
        ] as $participant) {
            Participant::create($participant + ['project_id' => $project->id]);
        }
    }
}
