<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceInvitationNotification;
use App\Support\PlanCatalog;
use App\Support\PlatformAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ManageWorkspaceTeam extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Team';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace settings';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Workspace team';

    protected string $view = 'filament.pages.manage-workspace-team';

    public string $inviteEmail = '';

    public string $inviteRole = 'member';

    public static function canAccess(): bool
    {
        return PlatformAccess::canUse(PlanCatalog::MODULE_TEAM)
            && (Filament::getTenant()?->canManageMembersBy(auth()->user()) ?? false);
    }

    public function getSubheading(): ?string
    {
        return 'Invite collaborators and control what they can change inside this workspace.';
    }

    public function invite(): void
    {
        $workspace = $this->workspace();
        $data = $this->validate([
            'inviteEmail' => ['required', 'email:rfc', 'max:255'],
            'inviteRole' => ['required', Rule::in(['admin', 'member', 'viewer'])],
        ]);
        $email = Str::lower(trim($data['inviteEmail']));

        if ($workspace->users()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            $this->addError('inviteEmail', 'This person already belongs to the workspace.');

            return;
        }

        $invitation = $workspace->invitations()->updateOrCreate(
            ['email' => $email],
            [
                'invited_by' => auth()->id(),
                'role' => $data['inviteRole'],
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ],
        );

        NotificationFacade::route('mail', $email)
            ->notify(new WorkspaceInvitationNotification($invitation));

        $this->resetInviteForm();
        Notification::make()->title('Invitation sent to '.$email)->success()->send();
    }

    public function updateRole(int $userId, string $role): void
    {
        $workspace = $this->workspace();
        abort_unless(in_array($role, ['admin', 'member', 'viewer'], true), 422);
        $member = $this->editableMember($workspace, $userId);

        $workspace->users()->updateExistingPivot($member->id, ['role' => $role]);
        Notification::make()->title($member->name.' is now '.ucfirst($role))->success()->send();
    }

    public function removeMember(int $userId): void
    {
        $workspace = $this->workspace();
        $member = $this->editableMember($workspace, $userId);

        $workspace->users()->detach($member->id);
        Notification::make()->title($member->name.' removed from the workspace')->success()->send();
    }

    public function resendInvitation(int $invitationId): void
    {
        $workspace = $this->workspace();
        $invitation = $workspace->invitations()->whereKey($invitationId)->whereNull('accepted_at')->firstOrFail();
        $invitation->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'invited_by' => auth()->id(),
        ]);

        NotificationFacade::route('mail', $invitation->email)
            ->notify(new WorkspaceInvitationNotification($invitation));
        Notification::make()->title('Invitation sent again')->success()->send();
    }

    public function cancelInvitation(int $invitationId): void
    {
        $workspace = $this->workspace();
        $workspace->invitations()->whereKey($invitationId)->whereNull('accepted_at')->firstOrFail()->delete();
        Notification::make()->title('Invitation cancelled')->success()->send();
    }

    public function getMembersProperty()
    {
        return $this->workspace()->users()->orderBy('name')->get();
    }

    public function getPendingInvitationsProperty()
    {
        return $this->workspace()->invitations()
            ->whereNull('accepted_at')
            ->latest()
            ->get();
    }

    private function workspace(): Workspace
    {
        $workspace = Filament::getTenant();
        abort_unless($workspace instanceof Workspace && $workspace->canManageMembersBy(auth()->user()), 403);

        return $workspace;
    }

    private function editableMember(Workspace $workspace, int $userId): User
    {
        abort_if($userId === auth()->id(), 422, 'You cannot change your own workspace access.');
        $member = $workspace->users()->whereKey($userId)->firstOrFail();
        abort_if($member->pivot->role === 'owner', 403, 'The workspace owner cannot be changed here.');

        return $member;
    }

    private function resetInviteForm(): void
    {
        $this->inviteEmail = '';
        $this->inviteRole = 'member';
        $this->resetErrorBag();
    }
}
