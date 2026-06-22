<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageWorkspaceTeam;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class WorkspaceTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_send_and_resend_a_workspace_invitation(): void
    {
        Notification::fake();
        [$workspace, $owner] = $this->workspaceAndUser('owner');
        $this->actingAs($owner);
        Filament::setTenant($workspace);

        Livewire::test(ManageWorkspaceTeam::class)
            ->assertSee('Invite a collaborator')
            ->set('inviteEmail', 'Partner@Example.org')
            ->set('inviteRole', 'viewer')
            ->call('invite')
            ->assertHasNoErrors();

        $invitation = WorkspaceInvitation::query()->firstOrFail();
        $this->assertSame('partner@example.org', $invitation->email);
        $this->assertSame('viewer', $invitation->role);
        $this->assertTrue($invitation->expires_at->isFuture());
        Notification::assertSentOnDemand(WorkspaceInvitationNotification::class);

        $oldToken = $invitation->token;
        Livewire::test(ManageWorkspaceTeam::class)
            ->call('resendInvitation', $invitation->id);

        $this->assertNotSame($oldToken, $invitation->fresh()->token);
    }

    public function test_invited_user_can_accept_with_the_matching_email(): void
    {
        [$workspace, $owner] = $this->workspaceAndUser('owner');
        $invitedUser = User::factory()->create(['email' => 'partner@example.org']);
        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'invited_by' => $owner->id,
            'email' => 'partner@example.org',
            'role' => 'member',
            'token' => str_repeat('a', 64),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($invitedUser)
            ->get(route('workspace-invitations.accept', $invitation->token))
            ->assertRedirect('/app/'.$workspace->slug);

        $this->assertSame('member', $workspace->roleFor($invitedUser));
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_invitation_rejects_a_different_email_and_expired_token(): void
    {
        [$workspace, $owner] = $this->workspaceAndUser('owner');
        $wrongUser = User::factory()->create(['email' => 'wrong@example.org']);
        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'invited_by' => $owner->id,
            'email' => 'partner@example.org',
            'role' => 'viewer',
            'token' => str_repeat('b', 64),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($wrongUser)
            ->get(route('workspace-invitations.accept', $invitation->token))
            ->assertForbidden();

        $invitation->update(['expires_at' => now()->subMinute()]);
        $matchingUser = User::factory()->create(['email' => 'partner@example.org']);
        $this->actingAs($matchingUser)
            ->get(route('workspace-invitations.accept', $invitation->token))
            ->assertStatus(410);
    }

    public function test_owner_can_change_and_remove_members_but_cannot_modify_owner(): void
    {
        [$workspace, $owner] = $this->workspaceAndUser('owner');
        $member = User::factory()->create();
        $workspace->users()->attach($member, ['role' => 'member']);
        $this->actingAs($owner);
        Filament::setTenant($workspace);

        Livewire::test(ManageWorkspaceTeam::class)
            ->call('updateRole', $member->id, 'viewer');
        $this->assertSame('viewer', $workspace->roleFor($member));

        Livewire::test(ManageWorkspaceTeam::class)
            ->call('removeMember', $member->id);
        $this->assertNull($workspace->roleFor($member));
        $this->assertSame('owner', $workspace->roleFor($owner));
    }

    public function test_regular_member_cannot_access_team_management(): void
    {
        [$workspace, $member] = $this->workspaceAndUser('member');
        $this->actingAs($member);
        Filament::setTenant($workspace);

        $this->assertFalse(ManageWorkspaceTeam::canAccess());
        $this->get(ManageWorkspaceTeam::getUrl())->assertForbidden();
    }

    private function workspaceAndUser(string $role): array
    {
        $workspace = Workspace::create(['name' => 'Team Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => $role, 'joined_at' => now()]);

        return [$workspace, $user];
    }
}
