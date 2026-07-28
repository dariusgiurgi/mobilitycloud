<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_legal_pages_render_with_company_details(): void
    {
        foreach (['/terms', '/privacy', '/cookies', '/security'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('MobilityCloud', false)
                ->assertSee('XEOTYPE SRL', false)
                ->assertSee('RO48497754', false)
                ->assertSee('powered by Xeotype', false);
        }
    }

    public function test_terms_explain_immediate_unlock_and_invoice_due_date(): void
    {
        $this->get('/terms')
            ->assertOk()
            ->assertSee('implementation modules unlock immediately', false)
            ->assertSee('Payment is due by the due date', false)
            ->assertSee('overdue', false);
    }
}
