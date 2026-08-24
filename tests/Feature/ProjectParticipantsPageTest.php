<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectParticipants;
use App\Models\Participant;
use App\Models\ParticipantAttachment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectParticipantsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_surfaces_participant_and_document_readiness(): void
    {
        [$project, $user] = $this->projectAndUser();
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
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->call('openCreate')
            ->set('data.complete_name', 'Daria Marin')
            ->call('save')
            ->assertSee('Daria Marin');

        $this->assertDatabaseHas('participants', [
            'project_id' => $project->id,
            'complete_name' => 'Daria Marin',
            'first_name' => 'Daria',
            'last_name' => 'Marin',
        ]);
    }

    public function test_participant_mobilities_are_managed_from_the_participant_register(): void
    {
        [$project, $user] = $this->projectAndUser();
        $porto = $project->mobilities()->create(['name' => 'Porto', 'start_date' => '2026-07-01', 'end_date' => '2026-07-05']);
        $braga = $project->mobilities()->create(['name' => 'Braga', 'start_date' => '2026-08-01', 'end_date' => '2026-08-05', 'sort_order' => 1]);
        $this->actingAs($user);

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->call('openCreate')
            ->set('data.complete_name', 'Daria Marin')
            ->set('data.mobility_participations', [
                ['mobility_id' => $porto->id, 'role' => 'participant', 'status' => 'confirmed'],
                ['mobility_id' => $braga->id, 'role' => 'facilitator', 'status' => 'planned'],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Porto')
            ->assertSee('Braga');

        $participant = Participant::query()->where('project_id', $project->id)->where('complete_name', 'Daria Marin')->sole();
        $assignments = $participant->mobilities()->get()->keyBy('id');

        $this->assertCount(2, $assignments);
        $this->assertSame('confirmed', $assignments[$porto->id]->pivot->status);
        $this->assertSame('facilitator', $assignments[$braga->id]->pivot->role);
    }

    public function test_mobility_access_member_can_add_a_participant_from_the_register(): void
    {
        [$project, $user] = $this->projectAndUser(Project::PROJECT_ROLE_MOBILITY);
        $this->actingAs($user);

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->assertSee('Participant register')
            ->assertDontSee('Read-only access')
            ->call('openCreate')
            ->set('data.complete_name', 'Facilitator Participant')
            ->call('save')
            ->assertSee('Facilitator Participant');

        $this->assertDatabaseHas('participants', [
            'project_id' => $project->id,
            'complete_name' => 'Facilitator Participant',
        ]);
    }

    public function test_manager_can_create_and_close_public_participant_form_link(): void
    {
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);

        $component = Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->assertSee('Participant self-registration')
            ->assertSee('Create form link')
            ->call('createParticipantRegistrationLink')
            ->assertSee('Close form link')
            ->assertSee('Copy');

        $project->refresh();
        $this->assertNotNull($project->participant_registration_token);
        $this->assertTrue($project->hasActiveParticipantRegistrationLink());
        $this->assertSame(
            route('public.participant-registration.show', $project->participant_registration_token),
            $component->instance()->getParticipantRegistrationUrl(),
        );

        $component
            ->call('closeParticipantRegistrationLink')
            ->assertSee('Create form link');

        $this->assertFalse($project->fresh()->hasActiveParticipantRegistrationLink());
    }

    public function test_viewer_gets_participant_details_without_mutation_controls(): void
    {
        [$project, $viewer] = $this->projectAndUser(Project::PROJECT_ROLE_VIEWER);
        $participant = $this->participant($project, 'Ana', 'Popescu', 'Scoala de Jocuri', 'RO', '2000-01-01');
        $this->actingAs($viewer);

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

    private function projectAndUser(string $role = Project::PROJECT_ROLE_EDITOR): array
    {
        $user = User::factory()->create();
        $owner = $role === Project::PROJECT_ROLE_EDITOR
            ? $user
            : User::factory()->create();

        $project = Project::create([
            'owner_id' => $owner->id,
            'access_mode' => 'restricted',
            'name' => 'Youth Exchange',
            'status' => 'active',
            'ka_action' => 'ka152',
            'mobility_start_date' => '2026-07-01',
        ]);

        if (! $project->isOwnedBy($user)) {
            $project->members()->attach($user, ['role' => $role]);
        }

        return [$project, $user];
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
