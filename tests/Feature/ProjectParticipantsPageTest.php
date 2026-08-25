<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProjectParticipants;
use App\Models\Participant;
use App\Models\ParticipantAttachment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
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

    public function test_replacing_a_participant_document_keeps_the_old_file_until_the_database_swap_succeeds(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser();
        $participant = $this->participant($project, 'Ana', 'Popescu', 'Scoala de Jocuri', 'RO', '2000-01-01');
        $oldPath = 'participant-attachments/'.$participant->id.'/gdpr/old.pdf';
        Storage::disk('local')->put($oldPath, 'old document');
        $attachment = ParticipantAttachment::create([
            'participant_id' => $participant->id,
            'type' => 'gdpr',
            'path' => $oldPath,
            'disk' => 'local',
            'original_name' => 'old.pdf',
            'size' => 12,
        ]);

        $this->actingAs($user);

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->set('attachParticipantId', $participant->id)
            ->set('uploadType', 'gdpr')
            ->set('uploadFile', UploadedFile::fake()->create('new-consent.pdf', 12, 'application/pdf'))
            ->call('uploadAttachment')
            ->assertHasNoErrors();

        $replacement = ParticipantAttachment::query()->sole();
        $this->assertSame($attachment->id, $replacement->id);
        $this->assertNotSame($oldPath, $replacement->path);
        $this->assertSame('new-consent.pdf', $replacement->original_name);
        Storage::disk('local')->assertExists($replacement->path);
        Storage::disk('local')->assertMissing($oldPath);
    }

    public function test_a_failed_participant_document_database_swap_preserves_the_old_file_and_removes_the_new_file(): void
    {
        Storage::fake('local');
        [$project, $user] = $this->projectAndUser();
        $participant = $this->participant($project, 'Ana', 'Popescu', 'Scoala de Jocuri', 'RO', '2000-01-01');
        $oldPath = 'participant-attachments/'.$participant->id.'/gdpr/old.pdf';
        Storage::disk('local')->put($oldPath, 'old document');
        $attachment = ParticipantAttachment::create([
            'participant_id' => $participant->id,
            'type' => 'gdpr',
            'path' => $oldPath,
            'disk' => 'local',
            'original_name' => 'old.pdf',
            'size' => 12,
        ]);
        ParticipantAttachment::saving(function (ParticipantAttachment $saving) use ($oldPath): void {
            if ($saving->exists && $saving->isDirty('path') && $saving->path !== $oldPath) {
                throw new RuntimeException('Simulated participant attachment database failure.');
            }
        });

        $this->actingAs($user);

        try {
            Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
                ->set('attachParticipantId', $participant->id)
                ->set('uploadType', 'gdpr')
                ->set('uploadFile', UploadedFile::fake()->create('new-consent.pdf', 12, 'application/pdf'))
                ->call('uploadAttachment');

            $this->fail('The simulated database failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated participant attachment database failure.', $exception->getMessage());
        }

        $this->assertSame($oldPath, $attachment->fresh()->path);
        Storage::disk('local')->assertExists($oldPath);
        $this->assertSame([$oldPath], Storage::disk('local')->allFiles());
    }

    public function test_import_modal_offers_a_blank_template(): void
    {
        [$project, $user] = $this->projectAndUser();
        $this->actingAs($user);

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->call('openCsvModal')
            ->assertSee('Download blank template')
            ->assertDontSee('Nothing was imported');
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
            ->call('save')
            ->assertHasNoErrors();

        $participant = Participant::query()->where('project_id', $project->id)->where('complete_name', 'Daria Marin')->sole();

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->call('openParticipantMobilityModal', $participant->id)
            ->set('selectedParticipantMobilityIds', [$porto->id, $braga->id])
            ->call('saveParticipantMobilities')
            ->assertHasNoErrors()
            ->assertSee('Porto')
            ->assertSee('Braga');

        $assignments = $participant->mobilities()->get()->keyBy('id');

        $this->assertCount(2, $assignments);
        $this->assertSame('planned', $assignments[$porto->id]->pivot->status);
        $this->assertSame('participant', $assignments[$braga->id]->pivot->role);
    }

    public function test_participant_cannot_be_assigned_to_a_mobility_without_their_organisation(): void
    {
        [$project, $user] = $this->projectAndUser();
        $project->update([
            'partner_orgs' => [
                ['name' => 'Scoala de Jocuri', 'country' => 'Romania', 'oid' => 'E10000001'],
                ['name' => 'Youth Group Spain', 'country' => 'Spain', 'oid' => 'E20000002'],
            ],
        ]);
        $participant = $this->participant($project, 'Ana', 'Popescu', 'Scoala de Jocuri', 'RO', '2000-01-01');
        $madrid = $project->mobilities()->create([
            'name' => 'Madrid',
            'participating_organisations' => ['oid_e20000002'],
        ]);
        $this->actingAs($user);

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->call('openParticipantMobilityModal', $participant->id)
            ->set('selectedParticipantMobilityIds', [$madrid->id])
            ->call('saveParticipantMobilities')
            ->assertHasErrors('selectedParticipantMobilityIds');

        $this->assertDatabaseCount('mobility_participant', 0);
    }

    public function test_changing_an_organisation_cannot_invalidate_existing_mobility_assignments(): void
    {
        [$project, $user] = $this->projectAndUser();
        $project->update([
            'partner_orgs' => [
                ['name' => 'Scoala de Jocuri', 'country' => 'Romania', 'oid' => 'E10000001'],
                ['name' => 'Youth Group Spain', 'country' => 'Spain', 'oid' => 'E20000002'],
            ],
        ]);
        $participant = $this->participant($project, 'Ana', 'Popescu', 'Scoala de Jocuri', 'RO', '2000-01-01');
        $bucharest = $project->mobilities()->create([
            'name' => 'Bucharest',
            'participating_organisations' => ['oid_e10000001'],
        ]);
        $participant->mobilities()->attach($bucharest->id, ['role' => 'participant', 'status' => 'planned']);
        $this->actingAs($user);

        Livewire::test(ViewProjectParticipants::class, ['record' => $project->id])
            ->call('openEdit', $participant->id)
            ->set('data.partner_organisation', 'Youth Group Spain')
            ->call('save')
            ->assertHasErrors('data.partner_organisation');

        $this->assertSame('Scoala de Jocuri', $participant->fresh()->partner_organisation);
        $this->assertTrue($participant->mobilities()->whereKey($bucharest->id)->exists());
    }

    public function test_minor_and_parental_consent_follow_every_assigned_mobility_date(): void
    {
        [$project] = $this->projectAndUser();
        $beforeBirthday = $project->mobilities()->create([
            'name' => 'Summer mobility',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-07',
        ]);
        $afterBirthday = $project->mobilities()->create([
            'name' => 'Autumn mobility',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-07',
            'sort_order' => 1,
        ]);
        $participant = $this->participant($project, 'Mara', 'Ionescu', 'Scoala de Jocuri', 'RO', '2008-09-15');
        foreach (['gdpr', 'agreement'] as $type) {
            ParticipantAttachment::create([
                'participant_id' => $participant->id,
                'type' => $type,
                'path' => "participants/{$participant->id}/{$type}.pdf",
                'disk' => 'local',
                'original_name' => "{$type}.pdf",
                'size' => 100,
            ]);
        }

        $participant->mobilities()->sync([$afterBirthday->id]);
        $participant->unsetRelation('mobilities')->unsetRelation('attachments');
        $this->assertFalse($participant->isMinor());
        $this->assertTrue($participant->hasCompleteDocs());
        $this->assertSame('18', $participant->ageDisplay());

        $participant->mobilities()->sync([$beforeBirthday->id, $afterBirthday->id]);
        $participant->unsetRelation('mobilities')->unsetRelation('attachments');
        $this->assertTrue($participant->isMinor());
        $this->assertFalse($participant->hasCompleteDocs());
        $this->assertContains('parental', $participant->missingDocTypes());
        $this->assertSame('17–18', $participant->ageDisplay());
        $this->assertSame(['Summer mobility'], $participant->minorMobilityNames());
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
            ->assertSee('Create form link')
            ->call('openRegistrationLinksModal')
            ->assertSee('Participant form links')
            ->call('createParticipantRegistrationLink')
            ->assertSee('General participant form')
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
            ->assertSee('Create link');

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
            'partner_orgs' => [
                ['name' => 'Scoala de Jocuri', 'country' => 'Romania', 'oid' => 'E10000001', 'is_coordinator' => true],
            ],
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
