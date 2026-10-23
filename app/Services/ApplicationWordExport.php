<?php

namespace App\Services;

use App\Models\Project;
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
        $word->addTitleStyle(2, ['size' => 16, 'bold' => true, 'color' => '2563EB']);
        $word->addTitleStyle(3, ['size' => 12, 'bold' => true]);
        $section = $word->addSection(['marginTop' => 1000, 'marginBottom' => 1000]);
        $section->addTitle('Project Application', 1);
        $section->addText($project->name, ['bold' => true]);
        $section->addText('Grant reference: '.($project->grant_ref ?: '—'));
        $section->addText('Generated '.now()->toIso8601String());
        $section->addFooter()->addPreserveText('MobilityCloud · Page {PAGE} of {NUMPAGES}', ['size' => 9, 'color' => '64748B']);
        $category = null;
        $project->loadMissing(['applicationSections' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')]);
        foreach ($project->applicationSections as $question) {
            if ($question->category && $question->category !== $category) {
                $category = $question->category;
                $section->addTitle($category, 2);
            }
            $section->addTitle($question->title, 3);
            foreach (preg_split('/\R/u', (string) ($question->content ?: '— not answered —')) as $paragraph) {
                $section->addText($paragraph);
            }
            foreach (ApplicationTableDefinitions::forSection($question) as $definition) {
                $rows = ApplicationTableDefinitions::filledRows($question, $definition['key']);
                if (! count($rows)) {
                    continue;
                }
                $section->addText($definition['label'], ['bold' => true]);
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
