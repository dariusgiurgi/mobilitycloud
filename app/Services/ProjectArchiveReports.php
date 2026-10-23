<?php

namespace App\Services;

use App\Models\Project;
use App\Support\CsvExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ProjectArchiveReports
{
    public function add(Project $project, array $included, callable $add): void
    {
        if ($included['application']) {
            $add('01-application/application.pdf', Pdf::loadView('pdf.application-report', [
                'project' => $project, 'sections' => $project->applicationSections,
            ])->setPaper('a4')->output());
        }
        if ($included['budget']) {
            // Keep deleted expenses in JSON history, but never count them in the financial report.
            $reportProject = clone $project;
            $reportProject->setRelation('budgetLines', $project->budgetLines->map(function ($line) {
                $copy = clone $line;

                return $copy->setRelation('expenses', $line->expenses->reject->trashed()->values());
            }));
            $totalBudget = (float) $reportProject->budgetLines->sum('allocated_budget');
            $totalSpent = (float) $reportProject->budgetLines->sum(fn ($line) => $line->expenses->sum('amount_eur'));
            $add('04-budget-expenses/financial-report.pdf', Pdf::loadView('pdf.project-report', [
                'project' => $reportProject, 'totalBudget' => $totalBudget, 'totalSpent' => $totalSpent,
                'totalRemaining' => $totalBudget - $totalSpent,
            ])->setPaper('a4')->output());
        }
        if ($included['participants']) {
            $output = fopen('php://temp', 'w+b');
            try {
                fwrite($output, "\xEF\xBB\xBF");
                CsvExport::writeRow($output, ['Name', 'Organisation', 'Role', 'Country', 'Email', 'Phone', 'Mobilities'], ';');
                foreach ($project->participants as $participant) {
                    CsvExport::writeRow($output, [
                        $participant->fullName(), $participant->partner_organisation, $participant->roleLabel(),
                        $participant->country, $participant->email, $participant->phone,
                        $participant->mobilities->map(fn ($m) => $m->name.' ('.$m->pivot->role.', '.$m->pivot->status.')')->implode('; '),
                    ], ';');
                }
                rewind($output);
                $add('03-participants/participants.csv', stream_get_contents($output));
            } finally {
                fclose($output);
            }
        }
    }
}
