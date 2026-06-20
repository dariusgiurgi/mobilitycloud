<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_member_can_export_alphabetical_excel_safe_csv(): void
    {
        $workspace = Workspace::create(['name' => 'Export Workspace']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);
        $member = User::factory()->create();
        $workspace->users()->attach($member, ['role' => 'viewer']);

        Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Zoe',
            'last_name' => 'Zimmer',
            'email' => '=HYPERLINK("https://example.test")',
            'role' => 'participant',
        ]);
        Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Ana',
            'last_name' => 'Adams',
            'role' => 'group_leader',
        ]);

        $response = $this->actingAs($member)
            ->get(route('projects.export-participants', $project));

        $response->assertOk();
        $response->assertDownload('participants-youth-exchange.csv');
        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF\"Last name\",\"First name\"", $content);
        $this->assertLessThan(strpos($content, 'Zimmer,Zoe'), strpos($content, 'Adams,Ana'));
        $this->assertStringContainsString("'=HYPERLINK", $content);
    }

    public function test_outsider_cannot_export_participants(): void
    {
        $workspace = Workspace::create(['name' => 'Private Workspace']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Private Project',
            'status' => 'active',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('projects.export-participants', $project))
            ->assertForbidden();
    }
}
