<?php

namespace Tests\Feature;

use App\Filament\Resources\PlatformAnnouncements\Pages\CreatePlatformAnnouncement;
use App\Models\PlatformAnnouncement;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_owner_can_send_in_app_notification_to_client_users(): void
    {
        $owner = User::factory()->create([
            'role' => User::ROLE_PLATFORM_OWNER,
            'email_verified_at' => now(),
        ]);
        $client = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);
        $platformAdmin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($owner);
        Filament::setCurrentPanel('platform');

        Livewire::test(CreatePlatformAnnouncement::class)
            ->fillForm([
                'title' => 'Platform QA message',
                'message' => 'This message is delivered only to client users.',
                'severity' => 'info',
                'delivery_type' => 'notification',
                'audience' => 'client_users',
                'is_active' => true,
                'is_dismissible' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $communication = PlatformAnnouncement::query()->where('title', 'Platform QA message')->firstOrFail();

        $this->assertSame('notification', $communication->delivery_type);
        $this->assertNotNull($communication->notification_sent_at);
        $this->assertSame(1, $communication->notification_sent_count);
        $this->assertSame(1, $client->notifications()->count());
        $this->assertSame('Platform QA message', $client->notifications()->sole()->data['title']);
        $this->assertSame(0, $platformAdmin->notifications()->count());
    }
}
