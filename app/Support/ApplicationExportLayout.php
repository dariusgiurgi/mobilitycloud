<?php

namespace App\Support;

class ApplicationExportLayout
{
    /** Preserve every source row, rendering explicit headings separately from questions. */
    public static function sections(iterable $sections): array
    {
        $category = null;
        $layout = [];

        foreach ($sections as $section) {
            $key = (string) $section->question_key;
            $isHeading = str_starts_with($key, 'template-heading-') || str_starts_with($key, 'custom-heading-');
            $heading = null;

            if ($isHeading) {
                $heading = $section->title;
                $category = $section->title;
            } elseif (filled($section->category) && $section->category !== $category) {
                $heading = $section->category;
                $category = $section->category;
            }

            $layout[] = ['section' => $section, 'heading' => $heading, 'is_heading' => $isHeading];
        }

        return $layout;
    }
}
