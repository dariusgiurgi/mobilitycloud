<?php

namespace App\Http\Controllers;

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
        });

        return redirect('/app/'.$invitation->workspace->slug)
            ->with('status', 'You joined '.$invitation->workspace->name.'.');
    }
}
