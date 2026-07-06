<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Support\ApplicationTemplates;
use App\Support\PlanCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeedE2eQaData extends Command
{
    protected $signature = 'qa:seed-e2e
        {--fresh : Remove previously generated QA bot data before seeding}
        {--force : Allow running in production-like environments}';

    protected $description = 'Seed deterministic browser-test accounts, projects and project invitations for Playwright QA bots.';

    private const PASSWORD = 'MobilityCloudQA!2026';

    private const OWNER_EMAIL = 'qa.owner@mobilitycloud.test';

    private const EDITOR_EMAIL = 'qa.editor@mobilitycloud.test';

    private const VIEWER_EMAIL = 'qa.viewer@mobilitycloud.test';

    private const FREE_EMAIL = 'qa.free@mobilitycloud.test';

    private const ADMIN_EMAIL = 'qa.platform-owner@mobilitycloud.test';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Refusing to seed QA data in production without --force.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->purgeQaData();
        }

        $password = env('E2E_QA_PASSWORD', self::PASSWORD);

        $owner = $this->upsertUser(self::OWNER_EMAIL, 'QA Bot Owner', 'writer_pro', User::ROLE_USER, $password);
        $editor = $this->upsertUser(self::EDITOR_EMAIL, 'QA Bot Editor', 'free', User::ROLE_USER, $password);
        $viewer = $this->upsertUser(self::VIEWER_EMAIL, 'QA Bot Viewer', 'free', User::ROLE_USER, $password);
        $free = $this->upsertUser(self::FREE_EMAIL, 'QA Bot Free Account', 'free', User::ROLE_USER, $password);
        $admin = $this->upsertUser(self::ADMIN_EMAIL, 'QA Bot Platform Owner', 'demo', User::ROLE_PLATFORM_OWNER, $password);

        $ownedProject = $this->upsertProject($owner, 'QA Bot Owned Project', 'QA-OWN');
        $collaborationProject = $this->upsertProject($owner, 'QA Bot Collaboration Project', 'QA-COLLAB', 'ka152-you');
        $this->syncWritingSections($collaborationProject, 'ka152-you');
        $viewerProject = $this->upsertProject($owner, 'QA Bot Viewer Project', 'QA-VIEW', 'ka152-you');
        $this->syncWritingSections($viewerProject, 'ka152-you');
        $this->seedParticipants($viewerProject);
        $writingProject = $this->upsertProject($owner, 'QA Bot Writing KA152 Project', 'QA-WRITE', 'ka152-you');
        $this->syncWritingSections($writingProject, 'ka152-you');
        $budgetProject = $this->upsertProject($owner, 'QA Bot Active Budget Project', 'QA-BUDGET');
        $this->seedBudgetBoard($budgetProject, $owner);
        $participantsProject = $this->upsertProject($owner, 'QA Bot Participants Project', 'QA-PART');
        $this->seedParticipants($participantsProject);
        $freeOwnedProject = $this->upsertProject($free, 'QA Bot Free Owned Project', 'QA-FREE');

        $editorInvitation = $this->upsertInvitation($owner, $collaborationProject, $editor, Project::PROJECT_ROLE_EDITOR);
        $viewerInvitation = $this->upsertInvitation($owner, $viewerProject, $viewer, Project::PROJECT_ROLE_VIEWER);

        $state = [
            'base_url' => config('app.url'),
            'password' => $password,
            'users' => [
                'owner' => ['email' => self::OWNER_EMAIL, 'name' => $owner->name],
                'editor' => ['email' => self::EDITOR_EMAIL, 'name' => $editor->name],
                'viewer' => ['email' => self::VIEWER_EMAIL, 'name' => $viewer->name],
                'free' => ['email' => self::FREE_EMAIL, 'name' => $free->name],
                'admin' => ['email' => self::ADMIN_EMAIL, 'name' => $admin->name],
            ],
            'projects' => [
                'owned' => ['id' => $ownedProject->id, 'name' => $ownedProject->name],
                'collaboration' => ['id' => $collaborationProject->id, 'name' => $collaborationProject->name],
                'viewer' => ['id' => $viewerProject->id, 'name' => $viewerProject->name],
                'writing_ka152' => ['id' => $writingProject->id, 'name' => $writingProject->name],
                'budget_active' => ['id' => $budgetProject->id, 'name' => $budgetProject->name],
                'participants' => ['id' => $participantsProject->id, 'name' => $participantsProject->name],
                'free_owned' => ['id' => $freeOwnedProject->id, 'name' => $freeOwnedProject->name],
            ],
            'invitations' => [
                'editor' => [
                    'email' => $editorInvitation->email,
                    'token' => $editorInvitation->token,
                    'url' => route('project-invitations.accept', $editorInvitation->token),
                ],
                'viewer' => [
                    'email' => $viewerInvitation->email,
                    'token' => $viewerInvitation->token,
                    'url' => route('project-invitations.accept', $viewerInvitation->token),
                ],
            ],
        ];

        Storage::disk('local')->put('e2e-state.json', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('QA browser-test data is ready.');
        $this->line('State file: '.storage_path('app/private/e2e-state.json'));
        $this->line('Password: '.$password);

        return self::SUCCESS;
    }

    private function purgeQaData(): void
    {
        Cache::flush();

        DB::transaction(function (): void {
            $emails = $this->qaEmails();

            $qaUsers = User::withTrashed()
                ->whereIn('email', $emails)
                ->get();

            $qaUserIds = $qaUsers->pluck('id')->all();

            $qaProjectIds = Project::withTrashed()
                ->where(fn ($query) => $query
                    ->whereIn('owner_id', $qaUserIds)
                    ->orWhere('name', 'like', 'QA Bot%'))
                ->pluck('id')
                ->all();

            WorkspaceInvitation::query()
                ->where(fn ($query) => $query
                    ->whereIn('email', $emails)
                    ->orWhereIn('invited_by', $qaUserIds)
                    ->orWhereIn('project_id', $qaProjectIds))
                ->delete();

            DB::table('project_user')
                ->whereIn('project_id', $qaProjectIds)
                ->orWhereIn('user_id', $qaUserIds)
                ->delete();

            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $qaUserIds)
                ->delete();

            Project::withTrashed()
                ->whereIn('id', $qaProjectIds)
                ->get()
                ->each
                ->forceDelete();

            $qaUsers->each->forceDelete();
        });
    }

    private function upsertUser(string $email, string $name, string $plan, string $role, string $password): User
    {
        $defaults = PlanCatalog::accountDefaults($plan);

        /** @var User $user */
        $user = User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => $role,
                'plan' => $defaults['plan'],
                'subscription_status' => $defaults['subscription_status'],
                'feature_flags' => $defaults['feature_flags'],
                'plan_limits' => $defaults['plan_limits'],
                'email_verified_at' => now(),
                'is_suspended' => false,
                'suspended_at' => null,
                'suspended_by' => null,
                'suspension_category' => null,
                'suspension_reason' => null,
                'deleted_at' => null,
            ],
        );

        return $user;
    }

    private function upsertProject(User $owner, string $name, string $acronym, ?string $kaAction = null): Project
    {
        /** @var Project $project */
        $project = Project::withTrashed()->updateOrCreate(
            [
                'owner_id' => $owner->id,
                'name' => $name,
            ],
            [
                'workspace_id' => null,
                'access_mode' => 'restricted',
                'acronym' => $acronym,
                'ka_action' => $kaAction,
                'description' => 'Automated QA project. Safe to delete.',
                'status' => 'writing',
                'total_budget' => 1000,
                'first_tranche_pct' => 80,
                'withholding_tax_rate' => 10,
                'deleted_at' => null,
            ],
        );

        return $project;
    }

    private function syncWritingSections(Project $project, string $templateKey): void
    {
        $sections = ApplicationTemplates::sections($templateKey);

        foreach ($sections as $sortOrder => $section) {
            ProjectApplicationSection::query()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'question_key' => $section['key'],
                ],
                [
                    'title' => $section['title'],
                    'category' => $section['category'] ?? null,
                    'char_limit' => $section['char_limit'] ?? null,
                    'content' => $section['key'] === 'summary-objectives'
                        ? 'QA bot baseline answer for the official KA152 objectives question.'
                        : '',
                    'sort_order' => $sortOrder,
                    'application_tables' => null,
                    'review_status' => 'draft',
                    'internal_notes' => null,
                ],
            );
        }

        ProjectApplicationSection::query()
            ->where('project_id', $project->id)
            ->whereNotIn('question_key', collect($sections)->pluck('key')->all())
            ->delete();
    }

    private function seedBudgetBoard(Project $project, User $owner): void
    {
        $project->update([
            'status' => 'active',
            'total_budget' => 5000,
            'approved_budget' => 5000,
            'currencies' => [
                'RON' => 5,
            ],
        ]);

        $travel = $project->budgetLines()->where('title', 'Travel')->firstOrFail();
        $support = $project->budgetLines()->where('title', 'Organisational Support')->firstOrFail();

        $travel->update(['allocated_budget' => 3000]);
        $support->update(['allocated_budget' => 2000]);

        $travel->expenses()->updateOrCreate(
            ['description' => 'QA Bot train tickets'],
            [
                'reference_nr' => 'QA-INV-001',
                'expense_date' => now()->subDays(7)->toDateString(),
                'amount' => 500,
                'currency' => 'RON',
                'exchange_rate' => 5,
                'amount_eur' => 100,
                'is_civil_convention' => false,
                'position' => 0,
                'created_by' => $owner->id,
            ],
        );

        $support->expenses()->updateOrCreate(
            ['description' => 'QA Bot facilitation materials'],
            [
                'reference_nr' => 'QA-INV-002',
                'expense_date' => now()->subDays(5)->toDateString(),
                'amount' => 250,
                'currency' => 'EUR',
                'exchange_rate' => 1,
                'amount_eur' => 250,
                'is_civil_convention' => false,
                'position' => 0,
                'created_by' => $owner->id,
            ],
        );
    }

    private function seedParticipants(Project $project): void
    {
        $project->update([
            'status' => 'active',
            'mobility_start_date' => '2026-08-01',
            'partner_orgs' => [
                ['name' => 'Scoala de Jocuri', 'country' => 'Romania', 'is_coordinator' => true],
                ['name' => 'Youth Group Spain', 'country' => 'Spain', 'is_coordinator' => false],
            ],
        ]);

        $rows = [
            [
                'first_name' => 'Ana',
                'last_name' => 'Adams',
                'birth_date' => '2001-04-12',
                'partner_organisation' => 'Scoala de Jocuri',
                'country' => 'Romania',
                'role' => 'group_leader',
                'email' => 'ana.adams@example.test',
                'phone' => '+40123456789',
                'fewer_opportunities' => false,
            ],
            [
                'first_name' => 'Mara',
                'last_name' => 'Ionescu',
                'birth_date' => '2010-01-15',
                'partner_organisation' => 'Scoala de Jocuri',
                'country' => 'Romania',
                'role' => 'participant',
                'email' => 'mara.ionescu@example.test',
                'phone' => '+40987654321',
                'fewer_opportunities' => true,
                'guardian_name' => 'Ioana Ionescu',
                'guardian_contact' => '+40700000000',
            ],
            [
                'first_name' => 'Zoe',
                'last_name' => 'Zimmer',
                'birth_date' => '2005-11-20',
                'partner_organisation' => 'Youth Group Spain',
                'country' => 'Spain',
                'role' => 'participant',
                'email' => '=HYPERLINK("https://example.test")',
                'phone' => '+34123456789',
                'fewer_opportunities' => false,
                'dietary_restrictions' => 'Vegetarian',
            ],
        ];

        foreach ($rows as $row) {
            $project->participants()->updateOrCreate(
                [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                ],
                $row,
            );
        }
    }

    private function upsertInvitation(User $owner, Project $project, User $invitee, string $role): WorkspaceInvitation
    {
        /** @var WorkspaceInvitation $invitation */
        $invitation = WorkspaceInvitation::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'email' => $invitee->email,
            ],
            [
                'workspace_id' => null,
                'invited_by' => $owner->id,
                'role' => 'project_'.$role,
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ],
        );

        return $invitation;
    }

    /**
     * @return array<int, string>
     */
    private function qaEmails(): array
    {
        return [
            self::OWNER_EMAIL,
            self::EDITOR_EMAIL,
            self::VIEWER_EMAIL,
            self::FREE_EMAIL,
            self::ADMIN_EMAIL,
        ];
    }
}
