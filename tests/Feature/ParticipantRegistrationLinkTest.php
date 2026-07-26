<?php

namespace Tests\Feature;

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
}
