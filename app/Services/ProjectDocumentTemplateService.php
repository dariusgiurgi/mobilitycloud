<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDocument;
use App\Support\StoredFileReference;
use App\Support\StoredFileSwapResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProjectDocumentTemplateService
{
    public function __construct(
        private readonly StoredFileReplacementService $files,
    ) {}

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

        $filename = Str::slug($template['label']).'-'.Str::slug($project->acronym ?: $project->name).'.pdf';
        $path = 'project-documents/'.$project->id.'/generated-templates/'.$key.'/'.Str::uuid().'/'.$filename;

        return $this->files->replace(
            disk: 'local',
            path: $path,
            write: fn (): bool => Storage::disk('local')->put($path, $pdf),
            swap: function (StoredFileReference $newFile) use ($project, $key, $template, $filename): StoredFileSwapResult {
                Project::query()->lockForUpdate()->findOrFail($project->id);

                $document = ProjectDocument::query()
                    ->where('project_id', $project->id)
                    ->where('metadata->template_key', $key)
                    ->lockForUpdate()
                    ->first();
                $replacedFile = StoredFileReference::from(
                    $document?->file_disk,
                    $document?->file_path,
                    $document?->file_size,
                );

                $document ??= new ProjectDocument(['project_id' => $project->id]);
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
                    'file_path' => $newFile->path,
                    'file_disk' => $newFile->disk,
                    'file_name' => $filename,
                    'file_size' => $newFile->size,
                ]);
                $document->save();

                return new StoredFileSwapResult($document->fresh(), $replacedFile);
            },
            expectedSize: strlen($pdf),
        );
    }
}
