<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectMobility;
use Illuminate\Support\Str;

final class ProjectOrganisations
{
    /**
     * Return the project's organisations with the stable keys stored on a
     * mobility. The owner fallback mirrors the Mobility setup screen.
     *
     * @return array<int, array{key: string, name: string, country: ?string, oid: ?string, is_coordinator: bool}>
     */
    public static function forProject(Project $project): array
    {
        $partners = collect($project->partners)
            ->filter(fn (array $partner): bool => filled($partner['name'] ?? null))
            ->values();

        if ($partners->isEmpty()) {
            $owner = $project->owner();
            $settings = $owner?->document_settings ?? [];

            $partners = collect([[
                'name' => $settings['legal_name']
                    ?? $settings['brand_name']
                    ?? $owner?->name
                    ?? 'Coordinator organisation',
                'country' => null,
                'oid' => null,
                'is_coordinator' => true,
            ]]);
        }

        return $partners
            ->map(function (array $partner, int $index): array {
                $name = trim((string) ($partner['name'] ?? 'Organisation '.($index + 1)));

                return [
                    'key' => self::key($partner, $index),
                    'name' => $name,
                    'country' => filled($partner['country'] ?? null) ? trim((string) $partner['country']) : null,
                    'oid' => filled($partner['oid'] ?? null) ? trim((string) $partner['oid']) : null,
                    'is_coordinator' => (bool) ($partner['is_coordinator'] ?? false),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string, string> */
    public static function options(Project $project): array
    {
        return collect(self::forProject($project))
            ->mapWithKeys(fn (array $organisation): array => [
                $organisation['key'] => $organisation['name'].($organisation['country'] ? ' · '.$organisation['country'] : ''),
            ])
            ->all();
    }

    /**
     * Organisations selected for a mobility. Records created before explicit
     * organisation selection was introduced continue to include every project
     * organisation until they are saved again.
     *
     * @return array<int, array{key: string, name: string, country: ?string, oid: ?string, is_coordinator: bool}>
     */
    public static function forMobility(ProjectMobility $mobility): array
    {
        $project = $mobility->relationLoaded('project')
            ? $mobility->project
            : $mobility->project()->first();

        if (! $project) {
            return [];
        }

        $organisations = collect(self::forProject($project));
        $selected = $mobility->participating_organisations;

        if (! is_array($selected)) {
            return $organisations->all();
        }

        return $organisations
            ->whereIn('key', $selected)
            ->values()
            ->all();
    }

    public static function mobilityAllows(ProjectMobility $mobility, ?string $organisationName): bool
    {
        $name = self::normaliseName($organisationName);

        if ($name === '') {
            return false;
        }

        return collect(self::forMobility($mobility))
            ->contains(fn (array $organisation): bool => self::normaliseName($organisation['name']) === $name);
    }

    /** @return array<int, string> */
    public static function namesForMobility(ProjectMobility $mobility): array
    {
        return collect(self::forMobility($mobility))->pluck('name')->values()->all();
    }

    private static function key(array $partner, int $index): string
    {
        if (filled($partner['oid'] ?? null)) {
            return 'oid_'.Str::slug((string) $partner['oid'], '_');
        }

        $base = trim(($partner['name'] ?? 'organisation').'|'.($partner['country'] ?? '').'|'.$index);

        return 'org_'.substr(sha1($base), 0, 12);
    }

    private static function normaliseName(?string $name): string
    {
        return Str::lower((string) preg_replace('/\s+/u', ' ', trim((string) $name)));
    }
}
