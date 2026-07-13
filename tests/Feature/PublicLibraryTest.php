<?php

namespace Tests\Feature;

use App\Filament\Pages\PublicLibrary;
use App\Models\ContentBlock;
use App\Models\PublicContentBlock;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_explains_trust_and_import_behaviour(): void
    {
        [$workspace, $user, $author] = $this->workspaceAndUsers();
        $this->publicBlock($workspace, $author, [
            'title' => 'Inclusive preparation method',
            'is_proven' => true,
            'source_note' => 'Approved KA152 application, 2025',
        ]);

        $this->actingAs($user);

        Livewire::test(PublicLibrary::class)
            ->assertSee('Shared knowledge, reusable safely')
            ->assertSee('Importing a block')
            ->assertSee('Proven and official content')
            ->assertSee('Inclusive preparation method')
            ->assertSee('Proven')
            ->assertSee('Import copy');
    }

    public function test_import_creates_only_one_editable_workspace_copy(): void
    {
        [$workspace, $user, $author] = $this->workspaceAndUsers();
        $block = $this->publicBlock($workspace, $author);

        $this->actingAs($user);

        Livewire::test(PublicLibrary::class)
            ->call('import', $block->id)
            ->assertSee('In my library')
            ->call('import', $block->id);

        $this->assertSame(1, ContentBlock::where('owner_id', $user->id)->whereNull('workspace_id')->count());
        $this->assertSame(1, $block->fresh()->import_count);
    }

    public function test_proven_filter_updates_the_visible_results(): void
    {
        [$workspace, $user, $author] = $this->workspaceAndUsers();
        $this->publicBlock($workspace, $author, ['title' => 'Proven content', 'is_proven' => true]);
        $this->publicBlock($workspace, $author, ['title' => 'Community draft', 'is_proven' => false]);

        $this->actingAs($user);

        Livewire::test(PublicLibrary::class)
            ->assertSee('Community draft')
            ->set('provenOnly', true)
            ->assertSee('Proven content')
            ->assertDontSee('Community draft');
    }

    private function workspaceAndUsers(): array
    {
        $workspace = Workspace::create(['name' => 'Public Library Workspace']);
        $user = User::factory()->create();
        $author = User::factory()->create();

        return [$workspace, $user, $author];
    }

    private function publicBlock(Workspace $workspace, User $author, array $attributes = []): PublicContentBlock
    {
        return PublicContentBlock::create(array_merge([
            'user_id' => $author->id,
            'origin_workspace_id' => $workspace->id,
            'title' => 'Reusable activity plan',
            'category' => 'methodology',
            'ka_action' => 'ka152',
            'language' => 'en',
            'body' => 'A complete reusable activity planning approach for international groups.',
            'tags' => ['planning'],
            'is_proven' => false,
            'source_note' => null,
            'import_count' => 0,
            'likes_count' => 0,
            'is_hidden' => false,
        ], $attributes));
    }
}
