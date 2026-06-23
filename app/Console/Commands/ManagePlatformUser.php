<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManagePlatformUser extends Command
{
    protected $signature = 'platform:user
        {email : The user email address}
        {--role=platform_admin : platform_owner or platform_admin}
        {--name= : Name used when creating a new user}
        {--password= : Password used when creating a new user}
        {--demote : Remove platform privileges and set the account back to a regular user}';

    protected $description = 'Create, promote, demote or update an internal platform owner/admin account';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $role = (string) $this->option('role');
        $demote = (bool) $this->option('demote');

        if ($demote) {
            return $this->demote($email);
        }

        if (! array_key_exists($role, User::platformRoleOptions())) {
            throw ValidationException::withMessages([
                'role' => 'Role must be one of: '.implode(', ', array_keys(User::platformRoleOptions())),
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $name = trim((string) ($this->option('name') ?: str($email)->before('@')->replace(['.', '_', '-'], ' ')->title()));
            $password = (string) ($this->option('password') ?: str()->password(16));

            $this->validateNewUser($email, $name, $password);

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => $role,
            ]);

            $this->info('Created '.$user->email.' as '.User::platformRoleOptions()[$role].'.');

            if (! $this->option('password')) {
                $this->warn('Temporary password: '.$password);
            }

            return self::SUCCESS;
        }

        $user->update(['role' => $role]);

        $this->info('Updated '.$user->email.' to '.User::platformRoleOptions()[$role].'.');

        return self::SUCCESS;
    }

    private function demote(string $email): int
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error('No user found for '.$email.'.');

            return self::FAILURE;
        }

        $user->update(['role' => User::ROLE_USER]);

        $this->info('Demoted '.$user->email.' to regular user.');

        return self::SUCCESS;
    }

    private function validateNewUser(string $email, string $name, string $password): void
    {
        validator([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ])->validate();
    }
}
