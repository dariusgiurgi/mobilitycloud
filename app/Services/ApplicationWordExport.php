<?php

namespace App\Services;

use App\Models\Project;
use App\Support\ApplicationExportLayout;
use App\Support\ApplicationTableDefinitions;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use RuntimeException;

class ApplicationWordExport
{
    public function create(Project $project): string
    {
        Settings::setOutputEscapingEnabled(true);
        $word = new PhpWord;
        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(11);
        $word->addTitleStyle(1, ['size' => 22, 'bold' => true, 'color' => '172033']);
        $word->addTitleStyle(2, ['size' => 16, 'bold' => true, 'color' => '2563EB'], ['keepNext' => true, 'spaceBefore' => 200, 'spaceAfter' => 80]);
        $word->addTitleStyle(3, ['size' => 12, 'bold' => true], ['keepNext' => true, 'spaceBefore' => 160, 'spaceAfter' => 60]);
        $section = $word->addSection(['marginTop' => 1000, 'marginBottom' => 1000]);
        $section->addTitle('Project Application', 1);
        $section->addText($project->name, ['bold' => true]);
        $section->addText('Grant reference: '.($project->grant_ref ?: '—'));
        $section->addText('Generated '.now()->toIso8601String());
        $section->addFooter()->addPreserveText('MobilityCloud · Page {PAGE} of {NUMPAGES}', ['size' => 9, 'color' => '64748B']);
        $project->loadMissing(['applicationSections' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')]);
        foreach (ApplicationExportLayout::sections($project->applicationSections) as $entry) {
            $question = $entry['section'];
            if ($entry['heading'] !== null) {
                $section->addTitle($entry['heading'], 2);
            }
            if (! $entry['is_heading']) {
                $section->addTitle($question->title, 3);
            }
            $content = trim((string) $question->content);
            if ($content !== '' || ! $entry['is_heading']) {
                foreach (preg_split('/\R/u', $content !== '' ? $content : '— not answered —') as $paragraph) {
                    $section->addText($paragraph);
                }
            }
            foreach (ApplicationTableDefinitions::forSection($question) as $definition) {
                $rows = ApplicationTableDefinitions::filledRows($question, $definition['key']);
                if (! count($rows)) {
                    continue;
                }
                $section->addText($definition['label'], ['bold' => true], ['keepNext' => true]);
                $table = $section->addTable(['borderSize' => 4, 'borderColor' => 'CBD5E1', 'cellMargin' => 80]);
                $table->addRow(null, ['tblHeader' => true]);
                foreach ($definition['columns'] as $column) {
                    $table->addCell()->addText($column['label'], ['bold' => true]);
                }
                foreach ($rows as $row) {
                    $table->addRow();
                    foreach ($definition['columns'] as $column) {
                        $table->addCell()->addText((string) ($row[$column['field']] ?? ''));
                    }
                }
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'mobilitycloud-word-');
        if ($path === false) {
            throw new RuntimeException('Could not create Word document.');
        }
        try {
            IOFactory::createWriter($word, 'Word2007')->save($path);

            return $path;
        } catch (\Throwable $exception) {
            @unlink($path);
            throw $exception;
        }
    }
}
