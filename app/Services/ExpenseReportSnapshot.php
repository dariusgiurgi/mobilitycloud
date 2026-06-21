<?php

namespace App\Services;

use App\Models\Project;
use Carbon\CarbonInterface;

class ExpenseReportSnapshot
{
    public function build(Project $project, ?CarbonInterface $startDate = null, ?CarbonInterface $endDate = null): array
    {
        $expenses = $project->budgetLines()
            ->with(['expenses' => fn ($query) => $query
                ->when($startDate, fn ($query) => $query->whereDate('expense_date', '>=', $startDate))
                ->when($endDate, fn ($query) => $query->whereDate('expense_date', '<=', $endDate))
                ->orderBy('expense_date')
                ->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->flatMap(fn ($line) => $line->expenses->map(fn ($expense) => [
                'id' => $expense->id,
                'reference' => $expense->reference_nr ?: $this->expenseCode($project, $expense->id),
                'date' => $expense->expense_date?->toDateString(),
                'budget_category' => $line->title,
                'description' => $expense->description,
                'amount' => (float) $expense->amount,
                'currency' => $expense->currency ?: 'EUR',
                'exchange_rate' => (float) ($expense->exchange_rate ?: 1),
                'amount_eur' => (float) $expense->amount_eur,
                'evidence' => $expense->attachmentExists() ? 'Attached' : 'Missing',
                'evidence_name' => $expense->attachment_name,
                'notes' => $expense->notes,
            ]))
            ->values();

        $categoryTotals = $expenses
            ->groupBy('budget_category')
            ->map(fn ($rows, $category) => [
                'category' => $category,
                'amount_eur' => round((float) $rows->sum('amount_eur'), 2),
            ])
            ->values()
            ->all();

        return [
            'period_start' => $startDate?->toDateString(),
            'period_end' => $endDate?->toDateString(),
            'expense_count' => $expenses->count(),
            'total_eur' => round((float) $expenses->sum('amount_eur'), 2),
            'category_totals' => $categoryTotals,
            'expenses' => $expenses->all(),
        ];
    }

    private function expenseCode(Project $project, int $expenseId): string
    {
        return ($project->expense_prefix ?: 'EXP').'-'.str_pad(
            (string) $expenseId,
            (int) ($project->expense_pad_length ?: 3),
            '0',
            STR_PAD_LEFT
        );
    }
}
