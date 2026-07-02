<?php

namespace Tests\Feature;

use App\Filament\Resources\ContentBlocks\Pages\CreateContentBlock;
use App\Filament\Resources\ContentBlocks\Pages\ListContentBlocks;
use App\Models\ContentBlock;
use App\Models\User;
use App\Models\Workspace;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_presents_reusable_content_with_clear_context(): void
    {
        [$workspace, $user] = $this->workspaceAndUser();
        ContentBlock::create([
            'workspace_id' => $workspace->id,
            'title' => 'Participant safety approach',
            'category' => 'safety',
            'ka_action' => 'ka152',
            'language' => 'en',
            'body' => 'Our safeguarding approach begins with a shared risk assessment and clear reporting responsibilities.',
            'is_proven' => true,
            'source_note' => 'Approved KA152, 2025',
        ]);

        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(ListContentBlocks::class)
            ->assertSee('Browse public library')
            ->assertSee('New writing block')
            ->assertSee('Participant safety approach')
            ->assertSee('Our safeguarding approach begins')
            ->assertSee('Safety &amp; protection', escape: false)
            ->assertSee('KA152');
    }

    public function test_proven_content_requires_a_source(): void
    {
        [$workspace, $user] = $this->workspaceAndUser();
        $this->actingAs($user);
        Filament::setTenant($workspace);

        Livewire::test(CreateContentBlock::class)
            ->fillForm([
                'title' => 'Proven methodology',
                'body' => 'A reusable methodology description.',
                'category' => 'methodology',
                'ka_action' => 'ka152',
                'language' => 'en',
                'is_proven' => true,
                'source_note' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['source_note' => 'required']);
    }

    private function workspaceAndUser(): array
    {
        $workspace = Workspace::create(['name' => 'Content Workspace']);
        $user = User::factory()->create();
        $workspace->users()->attach($user, ['role' => 'member']);

        return [$workspace, $user];
    }
}
