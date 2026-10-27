<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_marketing_pages_render(): void
    {
        foreach (['/', '/features', '/pricing', '/guide', '/help', '/contact'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('MobilityCloud', false)
                ->assertDontSee('Live demo')
                ->assertDontSee('Explore live demo')
                ->assertDontSee('href="'.url('/demo').'"', false)
                ->assertSee('powered by Xeotype', false)
                ->assertSee('/app/login', false);
        }
    }

    public function test_retired_demo_urls_redirect_without_authenticating_a_visitor(): void
    {
        foreach (['/demo', '/demo/calendar', '/demo/projects/1', '/demo/projects/1/edit'] as $path) {
            $this->get($path)->assertStatus(301)->assertRedirect('/features');
            $this->assertGuest();
        }

        $this->assertArrayNotHasKey('demo', Filament::getPanels());
        $pages = $this->getJson('/agent.json')->assertOk()->json('public_pages');
        $this->assertNotContains(url('/demo'), $pages);
        $this->assertStringNotContainsString('/demo', file_get_contents(public_path('sitemap.xml')));
    }

    public function test_homepage_points_to_the_platform_and_legal_documents(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/app/register', false)
            ->assertSee('/app/login', false)
            ->assertSee('/terms', false)
            ->assertSee('/privacy', false)
            ->assertSee('/cookies', false)
            ->assertSee('/security', false);
    }
}
