<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Project;
use App\Models\Workspace;
use App\Services\BudgetTransferService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_moves_allocation_and_creates_audit_record(): void
    {
        [$project, $from, $to] = $this->projectWithBaskets();

        $transfer = app(BudgetTransferService::class)->transfer($project, $from, $to, 250, 'Reallocation');

        $this->assertSame(750.0, (float) $from->fresh()->allocated_budget);
        $this->assertSame(250.0, (float) $to->fresh()->allocated_budget);
        $this->assertSame('active', $transfer->status);
        $this->assertDatabaseHas('budget_transfers', [
            'id' => $transfer->id,
            'project_id' => $project->id,
            'reason' => 'Reallocation',
        ]);
    }

    public function test_transfer_rejects_amount_that_is_no_longer_available(): void
    {
        [$project, $from, $to] = $this->projectWithBaskets();
        Expense::create([
            'budget_line_id' => $from->id,
            'amount_eur' => 900,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only € 100.00 is available');

        app(BudgetTransferService::class)->transfer($project, $from, $to, 150);
    }

    public function test_transfer_cannot_cross_project_boundaries(): void
    {
        [$project, $from] = $this->projectWithBaskets();
        [, , $otherBasket] = $this->projectWithBaskets('Other project');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('must belong to this project');

        app(BudgetTransferService::class)->transfer($project, $from, $otherBasket, 10);
    }

    public function test_active_transfer_can_be_reversed(): void
    {
        [$project, $from, $to] = $this->projectWithBaskets();
        $transfer = app(BudgetTransferService::class)->transfer($project, $from, $to, 200);

        app(BudgetTransferService::class)->reverse($project, $transfer);

        $this->assertSame(1000.0, (float) $from->fresh()->allocated_budget);
        $this->assertSame(0.0, (float) $to->fresh()->allocated_budget);
        $this->assertSame('reversed', $transfer->fresh()->status);
    }

    public function test_transfer_cannot_be_reversed_after_destination_spent_the_funds(): void
    {
        [$project, $from, $to] = $this->projectWithBaskets();
        $transfer = app(BudgetTransferService::class)->transfer($project, $from, $to, 200);
        Expense::create([
            'budget_line_id' => $to->id,
            'amount_eur' => 50,
        ]);

        try {
            app(BudgetTransferService::class)->reverse($project, $transfer);
            $this->fail('Expected reversal to be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('already spent', $exception->getMessage());
        }

        $this->assertSame(800.0, (float) $from->fresh()->allocated_budget);
        $this->assertSame(200.0, (float) $to->fresh()->allocated_budget);
        $this->assertSame('active', $transfer->fresh()->status);
    }

    private function projectWithBaskets(string $name = 'Project'): array
    {
        $workspace = Workspace::create(['name' => $name.' workspace']);
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => $name,
            'status' => 'writing',
        ]);
        $from = $project->budgetLines()->where('title', 'Travel')->firstOrFail();
        $to = $project->budgetLines()->where('title', 'Individual Support')->firstOrFail();
        $from->update(['allocated_budget' => 1000]);
        $to->update(['allocated_budget' => 0]);

        return [$project, $from->fresh(), $to->fresh()];
    }
}
