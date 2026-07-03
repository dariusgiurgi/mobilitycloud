<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectParticipants;
use App\Models\Participant;
use App\Models\ParticipantAttachment;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectParticipantsWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_surfaces_participant_and_document_readiness(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $ready = $this->participant($project, 'Ana', 'Popescu', 'Scoala de Jocuri', 'RO', '2000-01-01');
        foreach (['gdpr', 'agreement'] as $type) {
            ParticipantAttachment::create([
                'participant_id' => $ready->id,
                'type' => $type,
                'path' => "participants/{$ready->id}/{$type}.pdf",
                'disk' => 'local',
                'original_name' => "{$type}.pdf",
                'size' => 100,
            ]);
        }
        $this->participant($project, 'Mara', 'Ionescu', 'Youth Group', 'RO', '2010-01-01');

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->assertSee('Participant register')
            ->assertSee('2 organisations')
            ->assertSee('Documents ready')
            ->assertSee('Documents incomplete')
            ->assertSee('Scoala de Jocuri')
            ->assertSee('ana@example.test')
            ->assertSee('COMPLETE')
            ->assertSee('3 MISSING')
            ->assertSee('Attendance list');
    }

    public function test_manager_can_add_a_participant_from_the_register(): void
    {
        [$workspace, $project, $user] = $this->workspaceProjectAndUser('member');
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->call('openCreate')
            ->set('data.first_name', 'Daria')
            ->set('data.last_name', 'Marin')
            ->call('save')
            ->assertSee('Daria Marin');

        $this->assertDatabaseHas('participants', [
            'project_id' => $project->id,
            'first_name' => 'Daria',
            'last_name' => 'Marin',
        ]);
    }

    public function test_viewer_gets_participant_details_without_mutation_controls(): void
    {
        [$workspace, $project, $viewer] = $this->workspaceProjectAndUser('viewer');
        $participant = $this->participant($project, 'Ana', 'Popescu', 'Scoala de Jocuri', 'RO', '2000-01-01');
        $this->actingAs($viewer);
        Filament::setTenant($workspace);

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->assertSee('Read-only access')
            ->assertDontSee('Import CSV')
            ->assertDontSee('Attendance list')
            ->assertDontSee('Add participant')
            ->call('openEdit', $participant->id)
            ->assertSee('View participant')
            ->assertSee('Close')
            ->assertDontSee('Save')
            ->assertDontSee('Upload');
    }

    private function workspaceProjectAndUser(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Participants Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => $role]);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'active',
            'ka_action' => 'ka152',
            'mobility_start_date' => '2026-07-01',
        ]);

        return [$workspace, $project, $user];
    }

    private function participant(Project $project, string $firstName, string $lastName, string $organisation, string $country, string $birthDate): Participant
    {
        return Participant::create([
            'project_id' => $project->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'partner_organisation' => $organisation,
            'country' => $country,
            'birth_date' => $birthDate,
            'role' => 'participant',
            'email' => strtolower($firstName).'@example.test',
        ]);
    }
}
