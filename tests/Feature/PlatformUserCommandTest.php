<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_owner_can_be_created_from_cli(): void
    {
        $this->artisan('platform:user', [
            'email' => 'owner@example.test',
            '--role' => User::ROLE_PLATFORM_OWNER,
            '--name' => 'Platform Owner',
            '--password' => 'secure-password',
        ])->assertSuccessful();

        $user = User::where('email', 'owner@example.test')->firstOrFail();

        $this->assertSame('Platform Owner', $user->name);
        $this->assertSame(User::ROLE_PLATFORM_OWNER, $user->role);
        $this->assertTrue($user->isPlatformOwner());
        $this->assertTrue($user->isPlatformAdmin());
        $this->assertTrue(Hash::check('secure-password', $user->password));
    }

    public function test_existing_user_can_be_promoted_to_platform_admin(): void
    {
        $user = User::factory()->create(['email' => 'employee@example.test', 'role' => User::ROLE_USER]);

        $this->artisan('platform:user', [
            'email' => 'employee@example.test',
            '--role' => User::ROLE_PLATFORM_ADMIN,
        ])->assertSuccessful();

        $this->assertSame(User::ROLE_PLATFORM_ADMIN, $user->fresh()->role);
        $this->assertFalse($user->fresh()->isPlatformOwner());
        $this->assertTrue($user->fresh()->isPlatformAdmin());
    }

    public function test_platform_user_can_be_demoted_to_regular_user(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.test', 'role' => User::ROLE_PLATFORM_ADMIN]);

        $this->artisan('platform:user', [
            'email' => 'admin@example.test',
            '--demote' => true,
        ])->assertSuccessful();

        $this->assertSame(User::ROLE_USER, $user->fresh()->role);
        $this->assertFalse($user->fresh()->isPlatformAdmin());
    }
}
