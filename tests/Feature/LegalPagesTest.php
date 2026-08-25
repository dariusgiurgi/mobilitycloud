<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_legal_pages_render_with_company_details(): void
    {
        foreach (['legal.terms', 'legal.privacy', 'legal.cookies', 'legal.security'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('MobilityCloud', false)
                ->assertSee('XEOTYPE SRL', false)
                ->assertSee('RO48497754', false)
                ->assertSee('powered by Xeotype', false);
        }
    }

    public function test_billing_page_explains_immediate_unlock_and_invoice_due_date(): void
    {
        $this->get(route('legal.billing'))
            ->assertOk()
            ->assertSee('implementation modules unlock immediately', false)
            ->assertSee('its payment deadline is the due date written on the invoice or payment notice', false)
            ->assertSee('does not automatically interrupt implementation access', false)
            ->assertSee('overdue', false);
    }

    public function test_legacy_legal_urls_redirect_permanently_to_canonical_pages(): void
    {
        foreach (['/terms' => '/legal/terms', '/privacy' => '/legal/privacy', '/cookies' => '/legal/cookies', '/security' => '/legal/security'] as $legacy => $canonical) {
            $this->get($legacy)->assertRedirect($canonical)->assertStatus(301);
        }
    }
}
