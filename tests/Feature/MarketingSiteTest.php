<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingSiteTest extends TestCase
{
    public function test_public_marketing_pages_render(): void
    {
        foreach (['/', '/features', '/pricing', '/guide', '/help', '/contact'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('MobilityCloud', false)
                ->assertSee('powered by Xeotype', false)
                ->assertSee('/app/login', false);
        }
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
