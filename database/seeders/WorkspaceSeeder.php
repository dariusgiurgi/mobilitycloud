<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Workspace;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        // Pentru fiecare user fără workspace, creează unul personal (Free) și fă-l Owner
        User::all()->each(function (User $user) {
            if ($user->workspaces()->count() > 0) {
                return;
            }

            $workspace = Workspace::create([
                'name' => $user->name . "'s Workspace",
                'plan' => 'free',
            ]);

            $workspace->users()->attach($user->id, [
                'role'      => 'owner',
                'joined_at' => now(),
            ]);

            $user->update(['current_workspace_id' => $workspace->id]);

            $this->command->info("Workspace creat pentru: {$user->email}");
        });
    }
}
