<?php

namespace App\Services;

use App\Models\Project;
use Carbon\CarbonInterface;

class ExpenseReportSnapshot
{
    public const ORDER_OPTIONS = [
        'date' => 'Chronological (oldest first)',
        'category' => 'Budget basket / category',
        'reference' => 'Expense reference',
        'evidence' => 'Supporting evidence status',
    ];

    public function build(
        Project $project,
        ?CarbonInterface $startDate = null,
        ?CarbonInterface $endDate = null,
        string $orderBy = 'date'
    ): array {
        $orderBy = array_key_exists($orderBy, self::ORDER_OPTIONS) ? $orderBy : 'date';
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
                '_category_order' => (int) $line->sort_order,
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

        $expenses = $expenses
            ->sort(fn (array $left, array $right): int => match ($orderBy) {
                'category' => [$left['_category_order'], $left['date'] ?: '9999-12-31', $left['id']]
                    <=> [$right['_category_order'], $right['date'] ?: '9999-12-31', $right['id']],
                'reference' => strnatcasecmp($left['reference'], $right['reference'])
                    ?: ($left['id'] <=> $right['id']),
                'evidence' => [$left['evidence'] === 'Missing' ? 0 : 1, $left['date'] ?: '9999-12-31', $left['id']]
                    <=> [$right['evidence'] === 'Missing' ? 0 : 1, $right['date'] ?: '9999-12-31', $right['id']],
                default => [$left['date'] ?: '9999-12-31', $left['id']]
                    <=> [$right['date'] ?: '9999-12-31', $right['id']],
            })
            ->values()
            ->map(function (array $row, int $index): array {
                unset($row['_category_order']);
                $row['row_number'] = $index + 1;

                return $row;
            });

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
            'order_by' => $orderBy,
            'order_label' => self::ORDER_OPTIONS[$orderBy],
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
