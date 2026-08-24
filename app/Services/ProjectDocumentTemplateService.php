<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProjectDocumentTemplateService
{
    public const TEMPLATES = [
        'participant_agreement' => [
            'label' => 'Participant agreement',
            'description' => 'Blank participation agreement with project details and signature fields.',
        ],
        'parental_consent' => [
            'label' => 'Parental consent',
            'description' => 'Blank consent form for a parent or legal guardian.',
        ],
        'gdpr_declaration' => [
            'label' => 'GDPR declaration',
            'description' => 'Blank data-information and consent form to complete with the organisation details.',
        ],
        'participation_certificate' => [
            'label' => 'Certificate of participation',
            'description' => 'Project certificate with the participant and signature fields intentionally blank.',
        ],
        'mobility_report' => [
            'label' => 'Mobility report',
            'description' => 'Structured blank report for one mobility, its activities and outcomes.',
        ],
    ];

    public function templates(): array
    {
        return self::TEMPLATES;
    }

    public function generate(Project $project, string $key): ProjectDocument
    {
        $template = self::TEMPLATES[$key] ?? null;

        if (! $template) {
            throw new InvalidArgumentException('Unknown project document template.');
        }

        $project->loadMissing('ownerAccount');

        $pdf = Pdf::loadView('pdf.project-document-template', [
            'project' => $project,
            'templateKey' => $key,
            'template' => $template,
        ])->setPaper('a4', 'portrait')->output();

        $existing = $project->documents()
            ->where('metadata->template_key', $key)
            ->first();

        if ($existing?->hasFile()) {
            Storage::disk($existing->file_disk ?: 'local')->delete($existing->file_path);
        }

        $document = $existing ?? new ProjectDocument(['project_id' => $project->id]);
        $document->fill([
            'type' => ProjectDocument::TYPE_UPLOAD,
            'category' => 'other',
            'title' => $template['label'],
            'document_date' => now()->toDateString(),
            'notes' => 'Generic project template. Personal names and signatures are intentionally left blank.',
            'metadata' => [
                'generated_template' => true,
                'template_key' => $key,
                'template_version' => 1,
            ],
            'generated_at' => now(),
        ]);
        $document->save();

        $filename = Str::slug($template['label']).'-'.Str::slug($project->acronym ?: $project->name).'.pdf';
        $path = 'project-documents/'.$project->id.'/generated-templates/'.$document->id.'/'.$filename;
        Storage::disk('local')->put($path, $pdf);

        $document->update([
            'file_path' => $path,
            'file_disk' => 'local',
            'file_name' => $filename,
            'file_size' => strlen($pdf),
        ]);

        return $document->fresh();
    }
}
