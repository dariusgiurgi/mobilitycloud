<?php

namespace App\Http\Controllers;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\WorkspaceInvitation;
use App\Services\ProjectInvitationNotificationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkspaceInvitationController extends Controller
{
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = WorkspaceInvitation::query()
            ->with(['project'])
            ->where('token', $token)
            ->firstOrFail();

        abort_unless($invitation->isPending(), 410, 'This invitation has expired or was already used.');

        if (! $request->user()) {
            $request->session()->put('url.intended', route('project-invitations.accept', $invitation->token));

            return redirect()
                ->route('filament.admin.auth.login')
                ->with('status', 'Sign in or create an account with '.$invitation->email.' to accept the project invitation.');
        }

        if ($request->user()->is_suspended) {
            return redirect()->route('account.suspended');
        }

        abort_unless(strcasecmp($request->user()->email, $invitation->email) === 0, 403, 'Sign in with the email address that received this invitation.');

        abort_unless($invitation->project_id && str_starts_with($invitation->role, 'project_'), 410, 'This invitation type is no longer supported.');
        abort_if(! $invitation->project, 410, 'This project invitation is no longer available because the project was removed.');

        DB::transaction(function () use ($request, $invitation): void {
            $projectRole = str($invitation->role)->after('project_')->toString();
            $projectRole = array_key_exists($projectRole, Project::projectRoleOptions()) ? $projectRole : Project::PROJECT_ROLE_EDITOR;

            $invitation->project?->members()->syncWithoutDetaching([
                $request->user()->id => ['role' => $projectRole],
            ]);

            Notification::make()
                ->title('Project access granted')
                ->body('You now have access to '.$invitation->project?->name.' as '.Project::projectRoleLabel($projectRole).'.')
                ->success()
                ->actions([
                    Action::make('openProject')
                        ->label('Open project')
                        ->button()
                        ->markAsRead()
                        ->url(ProjectResource::projectUrl($invitation->project, 'overview', $request->user())),
                ])
                ->sendToDatabase($request->user(), isEventDispatched: true);

            app(ProjectInvitationNotificationService::class)->markAccepted($invitation, $request->user());

            $invitation->accepted_at = now();
            $invitation->save();
        });

        if ($invitation->project) {
            return redirect(ProjectResource::projectUrl($invitation->project, 'overview', $request->user()))
                ->with('status', 'You now have access to '.$invitation->project->name.'.');
        }

        return redirect(Dashboard::getUrl(panel: 'admin'));
    }
}
