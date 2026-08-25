<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectActivityLog;
use App\Models\ProjectApplicationSection;
use App\Models\ProjectFinalArchive;
use App\Services\ParticipantCsvImporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectExportController extends Controller
{
    public function finalArchive(Project $project, ProjectFinalArchive $archive)
    {
        $this->authorizeProjectModule($project, 'finalisation');

        if ($response = $this->ensureFinalExportsAvailable($project)) {
            return $response;
        }

        abort_unless((int) $archive->project_id === (int) $project->id, 404);

        if ($archive->hasExpired()) {
            if ($archive->status === ProjectFinalArchive::STATUS_READY) {
                $archive->expire();
            }

            abort(410, 'This temporary archive has expired. Prepare a new one from Finalisation.');
        }

        abort_unless($archive->isReady(), 409, 'The archive is not ready yet.');

        $storage = Storage::disk($archive->disk ?: 'local');
        abort_unless(filled($archive->path) && $storage->exists($archive->path), 404);

        $stream = $storage->readStream($archive->path);
        abort_unless(is_resource($stream), 404);

        $archive->increment('download_count', 1, ['downloaded_at' => now()]);

        ProjectActivityLog::create([
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'event' => 'final_archive_downloaded',
            'subject_type' => ProjectFinalArchive::class,
            'subject_id' => $archive->id,
            'description' => 'downloaded the final project archive',
            'metadata' => [
                'archive_uuid' => $archive->uuid,
                'size' => $archive->size,
                'sha256' => $archive->sha256,
            ],
        ]);

        return response()->streamDownload(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, $archive->filename, [
            'Content-Type' => 'application/zip',
            'Content-Length' => (string) $archive->size,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function participantsCsv(Project $project)
    {
        $this->authorizeProjectModule($project, 'participants');

        if ($response = $this->ensureFinalExportsAvailable($project)) {
            return $response;
        }

        $participants = $project->participants()
            ->with(['attachments', 'mobilities', 'project'])
            ->orderBy('complete_name')
            ->orderBy('last_name')
            ->get();

        $filename = 'participants-'.Str::slug($project->name).'.csv';

        return response()->streamDownload(function () use ($participants): void {
            $output = fopen('php://output', 'wb');

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ParticipantCsvImporter::HEADERS, ';');

            foreach ($participants as $participant) {
                fputcsv($output, array_map($this->csvValue(...), [
                    $participant->fullName(),
                    $participant->partner_organisation,
                    $participant->roleLabel(),
                    $participant->country,
                    $participant->birth_date?->format('Y-m-d'),
                    $participant->ageDisplay(),
                    $participant->nationality,
                    $participant->gender,
                    $participant->email,
                    $participant->phone,
                    $participant->address,
                    $participant->medical_conditions,
                    $participant->allergies,
                    $participant->dietary_restrictions,
                    $participant->special_needs,
                    $participant->fewer_opportunities ? 'Yes' : 'No',
                    $participant->guardian_name,
                    $participant->guardian_contact,
                    $participant->gdpr_consented_at?->format('Y-m-d'),
                    $participant->hasCompleteDocs() ? 'Yes' : 'No',
                ]), ';');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function participantImportTemplate(Project $project)
    {
        $this->authorizeProjectModule($project, 'participants');

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ParticipantCsvImporter::HEADERS, ';');
            fclose($output);
        }, 'participant-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }

    public function report(Project $project)
    {
        $this->authorizeProjectModule($project, 'board');

        if ($response = $this->ensureFinalExportsAvailable($project)) {
            return $response;
        }

        $project->load(['budgetLines' => fn ($q) => $q->orderBy('sort_order'), 'budgetLines.expenses', 'ownerAccount']);

        $totalBudget = (float) $project->budgetLines->sum('allocated_budget');
        $totalSpent = (float) $project->budgetLines->sum(fn ($bl) => $bl->expenses->sum('amount_eur'));
        $totalRemaining = $totalBudget - $totalSpent;

        $pdf = Pdf::loadView('pdf.project-report', [
            'project' => $project,
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
            'totalRemaining' => $totalRemaining,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('report-'.Str::slug($project->name).'.pdf');
    }

    public function calcExport(Request $request, string $type)
    {
        abort_unless(Auth::check(), 403);

        $bands = [
            ['label' => '0–9 km',       'green' => 0,    'standard' => 0],
            ['label' => '10–99 km',     'green' => 56,   'standard' => 28],
            ['label' => '100–499 km',   'green' => 285,  'standard' => 211],
            ['label' => '500–1999 km',  'green' => 417,  'standard' => 309],
            ['label' => '2000–2999 km', 'green' => 535,  'standard' => 395],
            ['label' => '3000–3999 km', 'green' => 785,  'standard' => 580],
            ['label' => '4000–7999 km', 'green' => 1188, 'standard' => 1188],
            ['label' => '8000+ km',     'green' => 1735, 'standard' => 1735],
        ];

        $participants = max(1, (int) $request->query('participants', 1));
        $days = max(1, (int) $request->query('days', 7));
        $isRate = (float) $request->query('isRate', 79);
        $travelDays = (int) $request->query('travelDays', 2);
        $isTravelInc = (bool) $request->query('isTravelDaysIncluded', 1);
        $bandIdx = (int) $request->query('travelBandIndex', 2);
        $green = (bool) $request->query('greenTravel', 0);
        $osRate = (float) $request->query('osRate', 100);
        $includeOS = (bool) $request->query('includeOS', 1);

        $band = $bands[$bandIdx] ?? $bands[0];
        $totalDays = $days + ($isTravelInc ? $travelDays : 0);
        $isTotal = round($participants * $totalDays * $isRate, 2);
        $travelPer = $green ? $band['green'] : $band['standard'];
        $travelTotal = round($participants * $travelPer, 2);
        $osTotal = $includeOS ? round($participants * $osRate, 2) : 0;
        $grand = $isTotal + $travelTotal + $osTotal;

        $pdf = Pdf::loadView('pdf.calc-report', [
            'participants' => $participants, 'days' => $days, 'isRate' => $isRate,
            'travelDays' => $travelDays, 'isTravelInc' => $isTravelInc,
            'bandLabel' => $band['label'], 'green' => $green, 'travelPer' => $travelPer,
            'osRate' => $osRate, 'includeOS' => $includeOS,
            'isTotal' => $isTotal, 'travelTotal' => $travelTotal, 'osTotal' => $osTotal, 'grand' => $grand,
            'workspace' => null,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('individual-support-calculation.pdf');
    }

    public function exportApplication(Project $project)
    {
        $this->authorizeProjectModule($project, 'write');

        if ($response = $this->ensureFinalExportsAvailable($project)) {
            return $response;
        }

        $sections = ProjectApplicationSection::where('project_id', $project->id)
            ->orderBy('sort_order')->orderBy('id')->get();

        $pdf = Pdf::loadView('pdf.application-report', [
            'project' => $project,
            'sections' => $sections,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('application-'.Str::slug($project->name).'.pdf');
    }

    public function exportApplicationWord(Project $project)
    {
        $this->authorizeProjectModule($project, 'write');

        if ($response = $this->ensureFinalExportsAvailable($project)) {
            return $response;
        }

        $project->loadMissing('ownerAccount');

        $sections = ProjectApplicationSection::where('project_id', $project->id)
            ->orderBy('sort_order')->orderBy('id')->get();

        $html = view('pdf.application-report', [
            'project' => $project,
            'sections' => $sections,
            'forWord' => true,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="application-'.Str::slug($project->name).'.doc"',
        ]);
    }

    public function exportApplicationPack(Project $project)
    {
        $this->authorizeProjectModule($project, 'write');

        if ($response = $this->ensureFinalExportsAvailable($project)) {
            return $response;
        }

        $project->loadMissing(['ownerAccount', 'participants', 'budgetLines.expenses', 'tasks.assignee']);

        $sections = ProjectApplicationSection::where('project_id', $project->id)
            ->orderBy('sort_order')->orderBy('id')->get();

        $flows = collect((array) (($project->action_data ?? [])['application_flows'] ?? []))
            ->filter(fn ($flow) => collect((array) $flow)->filter(fn ($value) => filled($value))->isNotEmpty())
            ->values()
            ->all();

        $budgetLines = $project->budgetLines->map(function ($line) {
            $spent = (float) $line->expenses->sum('amount_eur');

            return [
                'title' => $line->title,
                'allocated' => (float) $line->allocated_budget,
                'spent' => $spent,
                'remaining' => (float) $line->allocated_budget - $spent,
            ];
        })->values();

        $pdf = Pdf::loadView('pdf.application-pack', [
            'project' => $project,
            'sections' => $sections,
            'flows' => $flows,
            'budgetLines' => $budgetLines,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('application-pack-'.Str::slug($project->name).'.pdf');
    }

    private function authorizeProjectModule(Project $project, string $module): void
    {
        abort_unless(
            Auth::check() && $project->canAccessProjectModule(Auth::user(), $module),
            403
        );
    }

    private function ensureFinalExportsAvailable(Project $project)
    {
        if (! $project->exportsLockedUntilPayment()) {
            return null;
        }

        return response()->view('errors.project-payment-locked', [
            'project' => $project,
        ], 402);
    }
}
