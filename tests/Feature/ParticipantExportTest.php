<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_owner_can_export_alphabetical_excel_safe_csv(): void
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'access_mode' => 'restricted',
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);

        Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Zoe',
            'last_name' => 'Zimmer',
            'email' => '=HYPERLINK("https://example.test")',
            'address' => 'Main Street 10',
            'dietary_restrictions' => 'Vegetarian',
            'guardian_name' => 'Alex Zimmer',
            'role' => 'participant',
        ]);
        Participant::create([
            'project_id' => $project->id,
            'first_name' => 'Ana',
            'last_name' => 'Adams',
            'role' => 'group_leader',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('projects.export-participants', $project));

        $response->assertOk();
        $response->assertDownload('participants-youth-exchange.csv');
        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF\"Complete name\"", $content);
        $this->assertLessThan(strpos($content, 'Zoe Zimmer'), strpos($content, 'Ana Adams'));
        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringContainsString('Main Street 10', $content);
        $this->assertStringContainsString('Vegetarian', $content);
        $this->assertStringContainsString('Alex Zimmer', $content);
    }

    public function test_project_owner_can_download_a_blank_participant_import_template(): void
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'access_mode' => 'restricted',
            'name' => 'Youth Exchange',
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('projects.participant-import-template', $project));

        $response->assertOk();
        $response->assertDownload('participant-import-template.csv');
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF\"Complete name\";Organisation;Role", $content);
        $this->assertSame(1, substr_count(trim($content), "\n") + 1);
    }

    public function test_outsider_cannot_export_participants(): void
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'owner_id' => $owner->id,
            'access_mode' => 'restricted',
            'name' => 'Private Project',
            'status' => 'active',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('projects.export-participants', $project))
            ->assertForbidden();
    }
}
