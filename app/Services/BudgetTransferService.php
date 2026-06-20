<?php

namespace App\Services;

use App\Models\BudgetLine;
use App\Models\BudgetTransfer;
use App\Models\Project;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class BudgetTransferService
{
    public function transfer(
        Project $project,
        BudgetLine $from,
        BudgetLine $to,
        float $amount,
        ?string $reason = null,
        ?User $creator = null,
    ): BudgetTransfer {
        if ($amount <= 0) {
            throw new DomainException('Transfer amount must be greater than zero.');
        }

        if ($from->id === $to->id) {
            throw new DomainException('Source and destination must differ.');
        }

        return DB::transaction(function () use ($project, $from, $to, $amount, $reason, $creator): BudgetTransfer {
            $lines = BudgetLine::query()
                ->where('project_id', $project->id)
                ->whereIn('id', [$from->id, $to->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $fromLocked = $lines->get($from->id);
            $toLocked = $lines->get($to->id);

            if (! $fromLocked || ! $toLocked) {
                throw new DomainException('Both budget baskets must belong to this project.');
            }

            $available = (float) $fromLocked->allocated_budget
                - (float) $fromLocked->expenses()->sum('amount_eur');

            if ($amount > $available) {
                throw new DomainException(
                    'Only € '.number_format($available, 2).' is available in "'.$fromLocked->title.'".'
                );
            }

            $fromLocked->decrement('allocated_budget', $amount);
            $toLocked->increment('allocated_budget', $amount);

            return BudgetTransfer::create([
                'project_id' => $project->id,
                'from_budget_line_id' => $fromLocked->id,
                'to_budget_line_id' => $toLocked->id,
                'amount' => $amount,
                'reason' => $reason ?: null,
                'status' => 'active',
                'created_by' => $creator?->id,
            ]);
        });
    }

    public function reverse(Project $project, BudgetTransfer $transfer): BudgetTransfer
    {
        return DB::transaction(function () use ($project, $transfer): BudgetTransfer {
            $lockedTransfer = BudgetTransfer::query()
                ->where('project_id', $project->id)
                ->lockForUpdate()
                ->find($transfer->id);

            if (! $lockedTransfer || ! $lockedTransfer->isActive()) {
                throw new DomainException('This transfer is no longer active.');
            }

            $lines = BudgetLine::query()
                ->where('project_id', $project->id)
                ->whereIn('id', [$lockedTransfer->from_budget_line_id, $lockedTransfer->to_budget_line_id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $from = $lines->get($lockedTransfer->from_budget_line_id);
            $to = $lines->get($lockedTransfer->to_budget_line_id);

            if (! $from || ! $to) {
                throw new DomainException('The original budget baskets are no longer available.');
            }

            $amount = (float) $lockedTransfer->amount;
            $removable = (float) $to->allocated_budget - (float) $to->expenses()->sum('amount_eur');

            if ($amount > $removable) {
                throw new DomainException(
                    'This transfer cannot be reversed because the destination basket has already spent the transferred funds.'
                );
            }

            $to->decrement('allocated_budget', $amount);
            $from->increment('allocated_budget', $amount);
            $lockedTransfer->update(['status' => 'reversed', 'reversed_at' => now()]);

            return $lockedTransfer->refresh();
        });
    }
}
