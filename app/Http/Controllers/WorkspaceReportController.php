<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\WorkspaceReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkspaceReportController extends Controller
{
    public function accountCsv(Request $request, WorkspaceReportService $reports): StreamedResponse
    {
        $data = $this->validatedFilters($request);
        $report = $reports->build(null, $request->user(), $data);
        $filename = 'account-project-report-'.now()->format('Y-m-d').'.csv';

        return $this->streamReport($report, $filename);
    }

    public function csv(Request $request, Workspace $workspace, WorkspaceReportService $reports): StreamedResponse
    {
        abort_unless($workspace->users()->whereKey(auth()->id())->exists(), 403);
        $data = $this->validatedFilters($request);
        $report = $reports->build($workspace, auth()->user(), $data);
        $filename = 'workspace-report-'.Str::slug($workspace->name).'-'.now()->format('Y-m-d').'.csv';

        return $this->streamReport($report, $filename);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'status' => ['nullable', 'in:all,writing,submitted,rejected,revise,approved,active,completed'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);
    }

    private function streamReport(array $report, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Project', 'Acronym', 'Status', 'Funding EUR', 'Spent EUR', 'Remaining EUR', 'Expenses', 'Missing evidence', 'Participants', 'Start', 'End'], ';');
            foreach ($report['rows'] as $row) {
                fputcsv($out, [
                    $this->csvValue($row['project']), $this->csvValue($row['acronym']), $row['status'],
                    number_format($row['funding'], 2, '.', ''), number_format($row['spent'], 2, '.', ''), number_format($row['remaining'], 2, '.', ''),
                    $row['expenses'], $row['missing_evidence'], $row['participants'], $row['start_date'], $row['end_date'],
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvValue(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
