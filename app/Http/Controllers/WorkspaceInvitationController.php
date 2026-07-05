<?php

namespace App\Http\Controllers;

use App\Filament\Pages\Dashboard;
use App\Models\Project;
use App\Models\WorkspaceInvitation;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkspaceInvitationController extends Controller
{
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = WorkspaceInvitation::query()
            ->with(['workspace', 'project'])
            ->where('token', $token)
            ->firstOrFail();

        abort_unless($invitation->isPending(), 410, 'This invitation has expired or was already used.');

        if (! $request->user()) {
            $request->session()->put('url.intended', route('workspace-invitations.accept', $invitation->token));

            return redirect()
                ->route('filament.admin.auth.login')
                ->with('status', 'Sign in or create an account with '.$invitation->email.' to accept the workspace invitation.');
        }

        if ($request->user()->is_suspended) {
            return redirect()->route('account.suspended');
        }

        abort_unless(strcasecmp($request->user()->email, $invitation->email) === 0, 403, 'Sign in with the email address that received this invitation.');

        DB::transaction(function () use ($request, $invitation): void {
            if ($invitation->project_id && str_starts_with($invitation->role, 'project_')) {
                $projectRole = str($invitation->role)->after('project_')->toString();
                $projectRole = array_key_exists($projectRole, Project::projectRoleOptions()) ? $projectRole : Project::PROJECT_ROLE_EDITOR;

                $invitation->project?->members()->syncWithoutDetaching([
                    $request->user()->id => ['role' => $projectRole],
                ]);

                Notification::make()
                    ->title('Project access granted')
                    ->body('You now have access to '.$invitation->project?->name.' as '.Project::projectRoleLabel($projectRole).'.')
                    ->success()
                    ->sendToDatabase($request->user());
            } else {
                $invitation->workspace->users()->syncWithoutDetaching([
                    $request->user()->id => [
                        'role' => $invitation->role,
                        'joined_at' => now(),
                    ],
                ]);
            }

            $invitation->accepted_at = now();
            $invitation->save();

            $request->user()->forceFill([
                'current_workspace_id' => $invitation->workspace_id,
            ])->save();
        });

        return redirect(Dashboard::getUrl(panel: 'admin', tenant: $invitation->workspace))
            ->with('status', $invitation->project
                ? 'You now have access to '.$invitation->project->name.'.'
                : 'You joined '.$invitation->workspace->name.'.');
    }
}
