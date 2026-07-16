<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\PublicContentBlockSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformFreshStart extends Command
{
    protected $signature = 'platform:fresh-start
        {--owner-email= : Email for the recreated platform owner}
        {--owner-name= : Name for the recreated platform owner}
        {--owner-password= : Password for the recreated platform owner}
        {--seed-public-library : Seed official public library blocks}
        {--force : Required in production or non-interactive resets}';

    protected $description = 'Delete all application data, rebuild the database, and recreate a platform owner account';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to reset production without --force.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This deletes all database data. Continue?')) {
            $this->warn('Fresh start cancelled.');

            return self::SUCCESS;
        }

        $email = Str::lower(trim((string) ($this->option('owner-email') ?: config('mail.from.address') ?: 'owner@mobilitycloud.eu')));
        $name = trim((string) ($this->option('owner-name') ?: 'MobilityCloud Owner'));
        $password = (string) ($this->option('owner-password') ?: Str::password(18));

        $validator = validator([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->warn('Rebuilding database schema...');

        Artisan::call('migrate:fresh', [
            '--force' => true,
        ]);

        $this->line(Artisan::output());

        $owner = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_PLATFORM_OWNER,
            'plan' => 'unlimited',
            'subscription_status' => 'active',
            'feature_flags' => ['unlimited'],
            'plan_limits' => ['unlimited' => true],
            'billing_name' => $name,
            'billing_country' => 'Romania',
            'billing_address' => 'To be completed',
        ]);

        if ($this->option('seed-public-library')) {
            $this->call('db:seed', [
                '--class' => PublicContentBlockSeeder::class,
                '--force' => true,
            ]);
        }

        $this->info('Fresh platform database ready.');
        $this->line('Owner: '.$owner->email);

        if (! $this->option('owner-password')) {
            $this->warn('Generated password: '.$password);
        }

        return self::SUCCESS;
    }
}
