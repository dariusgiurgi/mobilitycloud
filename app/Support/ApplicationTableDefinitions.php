<?php

namespace App\Support;

use App\Models\ProjectApplicationSection;
use Illuminate\Support\Str;

class ApplicationTableDefinitions
{
    public static function forSection(ProjectApplicationSection $section): array
    {
        if (! $section->question_key) {
            return [];
        }

        $title = Str::of($section->title.' '.$section->category.' '.$section->question_key)->lower()->ascii()->toString();
        $tables = [];

        if (str_contains($title, 'select up to three topics')) {
            $tables[] = self::definition('project_topics', 'Project topics', [
                'topic' => 'Topic',
                'why_relevant' => 'Why it is relevant',
                'where_visible' => 'Where it appears in the project',
            ], 'Use this to keep the selected topics aligned with the narrative and activity design.');
        }

        if (str_contains($title, 'background of the participants') || str_contains($title, 'each participating group')) {
            $tables[] = self::definition('participant_groups', 'Participant groups', [
                'group' => 'Group / country',
                'participants' => 'Participants',
                'fewer_opportunities' => 'Fewer opportunities',
                'age_profile' => 'Age profile',
                'leaders' => 'Group leaders / support',
                'selection_logic' => 'Selection logic',
            ], 'Summarise each national group, participant profile, inclusion and support roles.');
        }

        if (str_contains($title, 'additional-funding-needs') || str_contains($title, 'table below') || str_contains($title, 'specific additional funding')) {
            $tables[] = self::definition('additional_funding', 'Additional funding needs', [
                'cost_type' => 'Cost type',
                'participants' => 'Participants / group',
                'description' => 'Description',
                'justification' => 'Justification',
                'estimated_cost' => 'Estimated cost',
            ], 'Use this for exceptional costs, inclusion support, visas, financial guarantee or expensive travel.');
        }

        if (str_contains($title, 'participant contribution') || str_contains($title, 'contributions from participants')) {
            $tables[] = self::definition('participant_contributions', 'Participant contributions', [
                'contribution_type' => 'Type',
                'amount' => 'Amount',
                'participants' => 'Who pays',
                'purpose' => 'Purpose',
                'barrier_mitigation' => 'Barrier mitigation',
            ], 'If contributions are planned, explain amount, purpose and how barriers are avoided.');
        }

        if (str_contains($title, 'fewer opportunities') || str_contains($title, 'situations are these participants facing') || str_contains($title, 'specific needs of these participants')) {
            $tables[] = self::definition('fewer_opportunities_support', 'Fewer opportunities support', [
                'barrier_type' => 'Barrier type',
                'participants' => 'Participants affected',
                'support_measure' => 'Support measure',
                'phase' => 'Phase',
                'responsible' => 'Responsible',
            ], 'Keep barriers, support measures and responsibilities explicit and non-stigmatising.');
        }

        if (str_contains($title, 'youthpass') || str_contains($title, 'europass') || str_contains($title, 'national instrument') || str_contains($title, 'certificate')) {
            $tables[] = self::definition('recognition_tools', 'Recognition tools', [
                'tool' => 'Tool / certificate',
                'purpose' => 'Purpose',
                'when_used' => 'When used',
                'responsible' => 'Responsible',
                'evidence' => 'Evidence / output',
            ], 'Map recognition tools to reflection moments and evidence of learning.');
        }

        if (str_contains($title, 'virtual') || str_contains($title, 'blended')) {
            $tables[] = self::definition('virtual_components', 'Virtual / blended components', [
                'phase' => 'Phase',
                'component' => 'Component',
                'participants' => 'Participants',
                'purpose' => 'Purpose',
                'tools' => 'Tools / platform',
            ], 'Map each virtual component to a purpose and project phase.');
        }

        if (str_contains($title, 'activity') || str_contains($title, 'activities') || str_contains($title, 'work package')) {
            $tables[] = str_contains($title, 'work package')
                ? self::definition('work_packages', 'Work package plan', [
                    'work_package' => 'Work package',
                    'objective' => 'Objective',
                    'activities' => 'Activities',
                    'lead_partner' => 'Lead partner',
                    'outputs' => 'Outputs',
                    'budget_logic' => 'Budget logic',
                ], 'A compact planning table helps keep work packages, responsibilities, outputs and budget logic consistent.')
                : self::definition('activity_plan', 'Activity plan', [
                    'activity' => 'Activity',
                    'type' => 'Type',
                    'participants' => 'Participants',
                    'duration' => 'Duration',
                    'countries' => 'Countries',
                    'responsible' => 'Responsible',
                    'output' => 'Output / result',
                ], 'A compact planning table helps keep activity narrative, participants, timing and outputs consistent.');
        }

        if (str_contains($title, 'evaluation') || str_contains($title, 'indicator') || str_contains($title, 'assess')) {
            $tables[] = self::definition('evaluation_matrix', 'Evaluation matrix', [
                'objective' => 'Objective',
                'indicator' => 'Indicator',
                'evidence' => 'Evidence source',
                'timing' => 'Timing',
                'responsible' => 'Responsible',
            ], 'Connect objectives with indicators, evidence and review moments.');
        }

        if (str_contains($title, 'dissemination') || str_contains($title, 'share') || str_contains($title, 'visible')) {
            $tables[] = self::definition('dissemination_plan', 'Dissemination plan', [
                'audience' => 'Audience',
                'message' => 'Message / result',
                'channel' => 'Channel',
                'timing' => 'Timing',
                'owner' => 'Owner',
                'evidence' => 'Evidence of reach',
            ], 'Plan audiences, channels and proof of reach so dissemination is not generic.');
        }

        return collect($tables)->unique('key')->values()->all();
    }

    public static function filledRows(ProjectApplicationSection $section, string $tableKey): array
    {
        return collect(($section->application_tables ?: [])[$tableKey] ?? [])
            ->map(fn ($row) => (array) $row)
            ->filter(fn (array $row) => collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isNotEmpty())
            ->values()
            ->all();
    }

    protected static function definition(string $key, string $label, array $columns, string $description): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'columns' => collect($columns)->map(fn (string $label, string $field) => [
                'field' => $field,
                'label' => $label,
            ])->values()->all(),
        ];
    }
}
