<?php

namespace App\Support;

class GuideKnowledge
{
    public static function chapters(string $globalFeeLabel): array
    {
        $chapters = json_decode(file_get_contents(resource_path('data/guide-answers.json')), true, 512, JSON_THROW_ON_ERROR);

        foreach ($chapters as &$chapter) {
            foreach ($chapter['items'] as &$item) {
                $item['answer'] = str_replace('{global_fee}', $globalFeeLabel, $item['answer']);
            }
            unset($item);
        }
        unset($chapter);

        return $chapters;
    }
}
