<?php

namespace App\Support;

class PlanCatalog
{
    public const MODULE_PROJECTS = 'projects';
    public const MODULE_WRITING = 'writing';
    public const MODULE_DOCUMENTS = 'documents';
    public const MODULE_PARTICIPANTS = 'participants';
    public const MODULE_BUDGET = 'budget';
    public const MODULE_PUBLIC_LIBRARY = 'public_library';
    public const MODULE_CONTENT_LIBRARY = 'content_library';
    public const MODULE_TASKS = 'tasks';
    public const MODULE_CURRENCIES = 'currencies';
    public const MODULE_CALCULATOR = 'calculator';
    public const MODULE_REPORTS = 'reports';
    public const MODULE_TEAM = 'team';
    public const MODULE_SETTINGS = 'settings';

    public static function plans(): array
    {
        return [
            'demo' => [
                'label' => 'Demo',
                'visibility' => 'internal',
                'description' => 'Internal sandbox plan for product testing, demos and onboarding presentations.',
                'modules' => array_keys(self::moduleOptions()),
                'limits' => [
                    'projects' => 10,
                    'members' => 10,
                    'storage_mb' => 2048,
                    'documents_per_month' => 100,
                    'ai_requests_per_month' => 250,
                    'exports_per_month' => 100,
                ],
            ],
            'free' => [
                'label' => 'Free',
                'visibility' => 'public',
                'description' => 'Entry plan for initial evaluation with strict workspace limits.',
                'modules' => array_keys(self::moduleOptions()),
                'limits' => [
                    'projects' => 1,
                    'members' => 2,
                    'storage_mb' => 100,
                    'documents_per_month' => 5,
                    'ai_requests_per_month' => 0,
                    'exports_per_month' => 5,
                ],
            ],
            'writer' => [
                'label' => 'Writer',
                'visibility' => 'public',
                'description' => 'Core operational plan for organisations managing a small portfolio of mobility projects.',
                'modules' => [
                    self::MODULE_PROJECTS,
                    self::MODULE_WRITING,
                    self::MODULE_BUDGET,
                    self::MODULE_PARTICIPANTS,
                    self::MODULE_DOCUMENTS,
                    self::MODULE_CONTENT_LIBRARY,
                    self::MODULE_TASKS,
                    self::MODULE_CURRENCIES,
                    self::MODULE_CALCULATOR,
                    self::MODULE_REPORTS,
                    self::MODULE_TEAM,
                    self::MODULE_SETTINGS,
                ],
                'limits' => [
                    'projects' => 5,
                    'members' => 5,
                    'storage_mb' => 1024,
                    'documents_per_month' => 50,
                    'ai_requests_per_month' => 100,
                    'exports_per_month' => 50,
                ],
            ],
            'writer_pro' => [
                'label' => 'Writer Pro',
                'visibility' => 'public',
                'description' => 'Full platform access for larger organisations with higher document, export and AI usage.',
                'recommended' => true,
                'modules' => array_keys(self::moduleOptions()),
                'limits' => [
                    'projects' => 50,
                    'members' => 25,
                    'storage_mb' => 10240,
                    'documents_per_month' => 500,
                    'ai_requests_per_month' => 1000,
                    'exports_per_month' => 500,
                ],
            ],
        ];
    }

    public static function planOptions(): array
    {
        return collect(self::plans())->mapWithKeys(fn (array $plan, string $key): array => [$key => $plan['label']])->all();
    }

    public static function moduleOptions(): array
    {
        return [
            self::MODULE_PROJECTS => 'Projects',
            self::MODULE_WRITING => 'Writing',
            self::MODULE_DOCUMENTS => 'Documents',
            self::MODULE_PARTICIPANTS => 'Participants',
            self::MODULE_BUDGET => 'Budget',
            self::MODULE_PUBLIC_LIBRARY => 'Public Library',
            self::MODULE_CONTENT_LIBRARY => 'Content Library',
            self::MODULE_TASKS => 'Tasks',
            self::MODULE_CURRENCIES => 'Currencies',
            self::MODULE_CALCULATOR => 'Individual Support Calculator',
            self::MODULE_REPORTS => 'Reports',
            self::MODULE_TEAM => 'Team',
            self::MODULE_SETTINGS => 'Workspace Settings',
        ];
    }

    public static function defaultModules(string $plan): array
    {
        return self::plans()[$plan]['modules'] ?? self::plans()['free']['modules'];
    }

    public static function defaultLimits(string $plan): array
    {
        return self::plans()[$plan]['limits'] ?? self::plans()['free']['limits'];
    }
}
