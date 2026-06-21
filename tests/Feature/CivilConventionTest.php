<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CivilConventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_convention_is_ready_only_when_required_details_are_present(): void
    {
        $workspace = Workspace::create(['name' => 'Convention Workspace']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'active',
            'withholding_tax_rate' => 10,
        ]);
        $expense = Expense::create([
            'budget_line_id' => $project->budgetLines()->first()->id,
            'description' => 'Facilitation services',
            'amount' => 1000,
            'currency' => 'EUR',
            'amount_eur' => 1000,
            'is_civil_convention' => true,
        ]);

        $this->assertFalse($expense->hasCompleteConventionData());

        $expense->update(['convention_data' => [
            'convention_number' => 'CC-001',
            'contract_date' => '2026-06-20',
            'provider_name' => 'Alex Example',
            'provider_address' => 'Bucharest',
            'provider_id_number' => 'AB123456',
            'service_description' => 'Facilitation services',
            'service_start_date' => '2026-07-01',
            'service_end_date' => '2026-07-07',
            'gross_amount' => '1000.00',
            'currency' => 'EUR',
        ]]);

        $this->assertTrue($expense->fresh()->hasCompleteConventionData());
    }

    public function test_workspace_member_can_generate_service_agreement_pdf(): void
    {
        $workspace = Workspace::create(['name' => 'Convention Workspace']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Youth Exchange',
            'status' => 'active',
            'withholding_tax_rate' => 10,
        ]);
        $member = User::factory()->create();
        $workspace->users()->attach($member, ['role' => 'viewer']);
        $expense = Expense::create([
            'budget_line_id' => $project->budgetLines()->first()->id,
            'description' => 'Facilitation services',
            'amount' => 1000,
            'currency' => 'EUR',
            'amount_eur' => 1000,
            'is_civil_convention' => true,
            'convention_data' => [
                'agreement_type' => 'service_agreement',
                'convention_number' => 'CC-001',
                'contract_date' => '2026-06-20',
                'contract_place' => 'Bucharest',
                'beneficiary_name' => 'Example Association',
                'provider_name' => 'Alex Example',
                'provider_address' => 'Bucharest',
                'provider_id_number' => 'AB123456',
                'service_description' => 'Facilitation services',
                'service_start_date' => '2026-07-01',
                'service_end_date' => '2026-07-07',
                'gross_amount' => '1000.00',
                'currency' => 'EUR',
                'payment_due_days' => '10',
                'acceptance_date' => '2026-07-08',
                'acceptance_deliverables' => 'Facilitation services and activity report',
                'acceptance_status' => 'accepted_without_reservations',
            ],
        ]);

        $response = $this->actingAs($member)
            ->get(route('project-documents.civil-convention', [$project, $expense]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $acceptance = $this->actingAs($member)
            ->get(route('project-documents.acceptance-certificate', [$project, $expense]));
        $acceptance->assertOk();
        $acceptance->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $acceptance->getContent());
    }

    public function test_copyright_assignment_requires_rights_details(): void
    {
        $expense = new Expense(['convention_data' => [
            'agreement_type' => 'copyright_assignment',
            'convention_number' => 'IP-001',
            'contract_date' => '2026-06-20',
            'provider_name' => 'Author Example',
            'provider_address' => 'Bucharest',
            'provider_id_number' => 'AB123456',
            'gross_amount' => '1000',
            'currency' => 'EUR',
        ]]);

        $this->assertFalse($expense->hasCompleteConventionData());

        $expense->convention_data = array_merge($expense->convention_data, [
            'work_description' => 'Educational methodology',
            'rights_scope' => 'Reproduction and distribution',
            'use_methods' => 'Print and digital use',
            'rights_duration' => '10 years',
            'rights_territory' => 'Worldwide',
        ]);

        $this->assertTrue($expense->hasCompleteConventionData());
    }

    public function test_acceptance_certificate_requires_acceptance_details(): void
    {
        $expense = new Expense(['convention_data' => [
            'convention_number' => 'CC-001',
            'contract_date' => '2026-06-20',
            'provider_name' => 'Alex Example',
            'provider_address' => 'Bucharest',
            'provider_id_number' => 'AB123456',
            'service_description' => 'Facilitation services',
            'service_start_date' => '2026-07-01',
            'service_end_date' => '2026-07-07',
            'gross_amount' => '1000.00',
            'currency' => 'EUR',
        ]]);

        $this->assertFalse($expense->hasCompleteAcceptanceData());

        $expense->convention_data = array_merge($expense->convention_data, [
            'acceptance_date' => '2026-07-08',
            'acceptance_deliverables' => 'Five workshops and activity report',
            'acceptance_status' => 'accepted_without_reservations',
        ]);

        $this->assertTrue($expense->hasCompleteAcceptanceData());
    }
}
