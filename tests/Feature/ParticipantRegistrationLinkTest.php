<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ParticipantRegistrationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_public_link_saves_multiple_participant_submissions(): void
    {
        $project = Project::create([
            'name' => 'Youth Exchange',
            'status' => 'approved',
            'partner_orgs' => [
                ['name' => 'Scoala de Jocuri', 'country' => 'Romania', 'is_coordinator' => true],
                ['name' => 'Youth Group Spain', 'country' => 'Spain'],
            ],
            'participant_registration_token' => Str::random(48),
            'participant_registration_opened_at' => now(),
        ]);

        $this->get(route('public.participant-registration.show', $project->participant_registration_token))
            ->assertOk()
            ->assertSee('Participant form')
            ->assertSee('Scoala de Jocuri')
            ->assertSee('Youth Group Spain');

        foreach (['Ana Popescu', 'Mara Ionescu'] as $name) {
            $this->post(route('public.participant-registration.store', $project->participant_registration_token), [
                'complete_name' => $name,
                'partner_organisation' => 'Scoala de Jocuri',
                'email' => Str::slug($name).'-participant@example.test',
                'birth_date' => '2000-01-01',
                'dietary_restrictions' => 'Vegetarian',
            ])->assertRedirect(route('public.participant-registration.show', $project->participant_registration_token));
        }

        $this->assertDatabaseHas('participants', [
            'project_id' => $project->id,
            'complete_name' => 'Ana Popescu',
            'partner_organisation' => 'Scoala de Jocuri',
            'country' => 'Romania',
            'role' => 'participant',
            'dietary_restrictions' => 'Vegetarian',
        ]);
        $this->assertDatabaseHas('participants', [
            'project_id' => $project->id,
            'complete_name' => 'Mara Ionescu',
        ]);
        $this->assertDatabaseCount('participants', 2);
    }

    public function test_closed_public_link_cannot_create_participants(): void
    {
        $project = Project::create([
            'name' => 'Youth Exchange',
            'status' => 'approved',
            'partner_orgs' => [
                ['name' => 'Scoala de Jocuri', 'country' => 'Romania'],
            ],
            'participant_registration_token' => Str::random(48),
            'participant_registration_opened_at' => now(),
            'participant_registration_closed_at' => now(),
        ]);

        $this->get(route('public.participant-registration.show', $project->participant_registration_token))
            ->assertOk()
            ->assertSee('currently closed');

        $this->post(route('public.participant-registration.store', $project->participant_registration_token), [
            'complete_name' => 'Ana Popescu',
            'partner_organisation' => 'Scoala de Jocuri',
        ])->assertNotFound();

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_general_form_can_register_one_person_for_multiple_mobilities(): void
    {
        $project = Project::create([
            'name' => 'Youth Exchange',
            'status' => 'approved',
            'partner_orgs' => [['name' => 'Scoala de Jocuri', 'country' => 'Romania']],
            'participant_registration_token' => Str::random(48),
            'participant_registration_opened_at' => now(),
        ]);
        $porto = $project->mobilities()->create(['name' => 'Porto']);
        $braga = $project->mobilities()->create(['name' => 'Braga', 'sort_order' => 1]);

        $this->get(route('public.participant-registration.show', $project->participant_registration_token))
            ->assertOk()
            ->assertSee('Select every mobility')
            ->assertSee('Porto')
            ->assertSee('Braga');

        $this->post(route('public.participant-registration.store', $project->participant_registration_token), [
            'complete_name' => 'Ana Popescu',
            'partner_organisation' => 'Scoala de Jocuri',
            'mobility_ids' => [$porto->id, $braga->id],
        ])->assertRedirect();

        $participant = Participant::query()->where('project_id', $project->id)->sole();
        $this->assertDatabaseCount('mobility_participant', 2);
        $this->assertTrue($participant->mobilities()->whereKey($porto->id)->exists());
        $this->assertTrue($participant->mobilities()->whereKey($braga->id)->exists());
    }

    public function test_mobility_specific_form_locks_the_registration_to_that_mobility(): void
    {
        $project = Project::create([
            'name' => 'Youth Exchange',
            'status' => 'approved',
            'partner_orgs' => [['name' => 'Scoala de Jocuri', 'country' => 'Romania']],
        ]);
        $porto = $project->mobilities()->create([
            'name' => 'Porto',
            'participant_registration_token' => Str::random(48),
            'participant_registration_opened_at' => now(),
        ]);
        $braga = $project->mobilities()->create(['name' => 'Braga', 'sort_order' => 1]);

        $this->get(route('public.participant-registration.show', $porto->participant_registration_token))
            ->assertOk()
            ->assertSee('This form is dedicated to this mobility')
            ->assertSee('Porto');

        $this->post(route('public.participant-registration.store', $porto->participant_registration_token), [
            'complete_name' => 'Mara Ionescu',
            'partner_organisation' => 'Scoala de Jocuri',
            'mobility_ids' => [$braga->id],
        ])->assertRedirect();

        $participant = Participant::query()->where('project_id', $project->id)->sole();
        $this->assertTrue($participant->mobilities()->whereKey($porto->id)->exists());
        $this->assertFalse($participant->mobilities()->whereKey($braga->id)->exists());
    }
}
