<?php

namespace App\Http\Controllers;

use App\Filament\Pages\Dashboard;
use App\Models\WorkspaceInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkspaceInvitationController extends Controller
{
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = WorkspaceInvitation::query()
            ->with('workspace')
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
            $invitation->workspace->users()->syncWithoutDetaching([
                $request->user()->id => [
                    'role' => $invitation->role,
                    'joined_at' => now(),
                ],
            ]);

            $invitation->accepted_at = now();
            $invitation->save();

            $request->user()->forceFill([
                'current_workspace_id' => $invitation->workspace_id,
            ])->save();
        });

        return redirect(Dashboard::getUrl(panel: 'admin', tenant: $invitation->workspace))
            ->with('status', 'You joined '.$invitation->workspace->name.'.');
    }
}
