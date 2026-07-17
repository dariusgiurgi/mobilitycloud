<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_account_is_redirected_to_email_verification_before_entering_the_app(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(Dashboard::getUrl(panel: 'admin'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_account_can_enter_the_app(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(Dashboard::getUrl(panel: 'admin'))
            ->assertOk();
    }

    public function test_verification_notice_can_resend_the_email_verification_link(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_signed_verification_link_marks_the_account_as_verified(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(Dashboard::getUrl(panel: 'admin'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_invited_account_must_verify_email_before_accepting_project_access(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $invited = User::factory()->unverified()->create(['email' => 'invited@example.test']);
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Verification Gate Project',
            'status' => 'writing',
        ]);
        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'email' => 'invited@example.test',
            'role' => 'project_editor',
            'token' => str_repeat('v', 64),
            'expires_at' => now()->addDays(3),
            'invited_by' => $owner->id,
        ]);

        $this->actingAs($invited)
            ->get(route('project-invitations.accept', $invitation->token))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($invited, VerifyEmail::class);
        $this->assertNull($invitation->fresh()->accepted_at);
        $this->assertDatabaseMissing('project_user', [
            'project_id' => $project->id,
            'user_id' => $invited->id,
        ]);

        $invited->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($invited)
            ->get(route('project-invitations.accept', $invitation->token))
            ->assertRedirect(ProjectResource::getUrl('overview', ['record' => $project], panel: 'admin'));

        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $invited->id,
            'role' => Project::PROJECT_ROLE_EDITOR,
        ]);
    }
}
