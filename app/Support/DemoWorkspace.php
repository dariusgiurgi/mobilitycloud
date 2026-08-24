<?php

namespace App\Support;

use App\Models\BudgetLine;
use App\Models\CalendarEvent;
use App\Models\Expense;
use App\Models\Participant;
use App\Models\Project;
use App\Models\ProjectApplicationSection;
use App\Models\ProjectDocument;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DemoWorkspace
{
    public const OWNER_EMAIL = 'demo-owner@mobilitycloud.invalid';

    public const VISITOR_EMAIL = 'demo-visitor@mobilitycloud.invalid';

    public const PROJECT_REFERENCE = 'MOBILITYCLOUD-LIVE-DEMO';

    public static function visitor(): ?User
    {
        return User::query()->where('email', self::VISITOR_EMAIL)->first();
    }

    public static function isVisitor(?User $user): bool
    {
        return $user?->email === self::VISITOR_EMAIL;
    }

    /** Creates only the deliberately public sample workspace. */
    public static function ensure(): Project
    {
        $owner = self::upsertUser(self::OWNER_EMAIL, 'MobilityCloud Demo');
        $visitor = self::upsertUser(self::VISITOR_EMAIL, 'Demo visitor');

        $project = Project::withTrashed()->firstOrNew(['grant_ref' => self::PROJECT_REFERENCE]);
        $project->fill([
            'owner_id' => $owner->id,
            'access_mode' => 'restricted',
            'name' => 'Green Skills Mobility',
            'acronym' => 'GSM 2026',
            'ka_action' => 'KA122-VET',
            'description' => 'A fictional Erasmus+ mobility project used only to demonstrate MobilityCloud.',
            'status' => 'approved',
            'total_budget' => 45800,
            'approved_budget' => 45800,
            'approved_grant_amount' => 45800,
            'approved_grant_currency' => 'EUR',
            'approved_declared_at' => now()->subDays(12),
            'is_activated' => true,
            'activated_at' => now()->subDays(12),
            'activation_tier' => 'demo',
            'activation_fee_amount' => 458,
            'activation_fee_currency' => 'EUR',
            'invoice_status' => Project::INVOICE_NOT_REQUIRED,
            'start_date' => now()->addDays(18)->toDateString(),
            'end_date' => now()->addDays(44)->toDateString(),
            'mobility_start_date' => now()->addDays(24)->toDateString(),
            'mobility_end_date' => now()->addDays(37)->toDateString(),
            'partner_org' => 'Porto Green Skills Centre',
            'partner_orgs' => [
                ['name' => 'GreenTech College', 'country' => 'Romania', 'oid' => 'E10299871', 'is_coordinator' => true],
                ['name' => 'Porto Green Skills Centre', 'country' => 'Portugal', 'oid' => 'E10321455', 'is_coordinator' => false],
            ],
            'notes' => 'Fictional read-only data. No customer data is used in this workspace.',
            'action_data' => self::actionData(),
        ]);
        $project->exists && $project->trashed() ? $project->restore() : null;
        $project->save();

        $project->members()->syncWithoutDetaching([$visitor->id => ['role' => Project::PROJECT_ROLE_VIEWER]]);

        $allocations = [12600, 24000, 9200, 0, 0];
        $project->budgetLines()->orderBy('sort_order')->get()->each(function (BudgetLine $line, int $index) use ($allocations): void {
            $line->update(['allocated_budget' => $allocations[$index] ?? 0]);
        });

        $lines = $project->budgetLines()->get()->keyBy('title');
        foreach ([
            ['Travel', 'DEMO-TR-001', 'Group rail tickets Bucharest – Porto', now()->addDays(23), 1680, 'EUR'],
            ['Travel', 'DEMO-TR-002', 'Local transfer: Porto airport to host centre', now()->addDays(24), 245, 'EUR'],
            ['Individual Support', 'DEMO-IS-001', 'Accommodation support · first mobility week', now()->addDays(25), 3240, 'EUR'],
            ['Individual Support', 'DEMO-IS-002', 'Daily subsistence · accompanying person', now()->addDays(25), 420, 'EUR'],
            ['Organisational Support', 'DEMO-OS-001', 'Green logistics workshop materials', now()->addDays(27), 860, 'EUR'],
        ] as $position => [$basket, $reference, $description, $date, $amount, $currency]) {
            $line = $lines->get($basket);

            if (! $line) {
                continue;
            }

            Expense::query()->updateOrCreate(
                ['budget_line_id' => $line->id, 'reference_nr' => $reference],
                [
                    'description' => $description,
                    'expense_date' => $date->toDateString(),
                    'amount' => $amount,
                    'currency' => $currency,
                    'exchange_rate' => 1,
                    'amount_eur' => $amount,
                    'is_civil_convention' => false,
                    'notes' => 'Fictional demo expense — no real payment or supplier is involved.',
                    'position' => $position,
                    'created_by' => $owner->id,
                ],
            );
        }

        foreach ([
            ['context-and-needs', 'Context and needs', 'The organisation supports vocational learners who need practical green skills and international learning opportunities.', 'ready', 0],
            ['project-objectives', 'Project objectives', 'Build green logistics skills, strengthen employability and share practical methods with local teachers.', 'ready', 1],
            ['impact-and-dissemination', 'Impact and dissemination', 'Plan a local sharing event after the mobility and publish the project outputs for partner organisations.', 'review', 2],
        ] as [$key, $title, $content, $review, $sort]) {
            ProjectApplicationSection::query()->updateOrCreate(
                ['project_id' => $project->id, 'question_key' => $key],
                ['title' => $title, 'content' => $content, 'review_status' => $review, 'category' => 'Application', 'sort_order' => $sort],
            );
        }

        foreach ([
            ['Ana Popescu', 'participant', 'ana.popescu@example.invalid'],
            ['Matei Ionescu', 'participant', 'matei.ionescu@example.invalid'],
            ['Elena Marin', 'accompanying_person', 'elena.marin@example.invalid'],
            ['Radu Ionescu', 'participant', 'radu.ionescu@example.invalid'],
            ['Ioana Stan', 'participant', 'ioana.stan@example.invalid'],
            ['Mihai Petrescu', 'participant', 'mihai.petrescu@example.invalid'],
        ] as [$name, $role, $email]) {
            Participant::query()->updateOrCreate(
                ['project_id' => $project->id, 'complete_name' => $name],
                ['role' => $role, 'country' => 'Romania', 'nationality' => 'Romanian', 'email' => $email, 'partner_organisation' => 'GreenTech College'],
            );
        }

        foreach ([
            ['Confirm travel details', 'Confirm travel dates and participant details before departure.', now()->addDays(10), 'high', 'open'],
            ['Collect participant agreements', 'Review the fictional agreement checklist before departure.', now()->addDays(14), 'normal', 'open'],
            ['Prepare first mobility day', 'Review the evidence checklist with the team.', now()->addDays(23), 'normal', 'open'],
            ['Kick-off meeting completed', 'Initial coordination meeting recorded in the demo timeline.', now()->subDays(3), 'low', 'completed'],
        ] as $sort => [$title, $description, $dueDate, $priority, $status]) {
            ProjectTask::query()->updateOrCreate(
                ['project_id' => $project->id, 'title' => $title],
                [
                    'description' => $description,
                    'due_date' => $dueDate,
                    'assigned_to' => $visitor->id,
                    'created_by' => $owner->id,
                    'completed_by' => $status === 'completed' ? $owner->id : null,
                    'completed_at' => $status === 'completed' ? now()->subDays(2) : null,
                    'priority' => $priority,
                    'status' => $status,
                    'sort_order' => $sort,
                ],
            );
        }

        foreach ([
            ['Grant agreement summary', 'grant_agreement', 'grant-agreement-summary.pdf', 'The fictional grant record used by this demo.', now()->subDays(12)],
            ['Partner cooperation note', 'partnership_agreement', 'partner-cooperation-note.pdf', 'Roles and working rhythm for the sample partners.', now()->subDays(9)],
            ['Mobility agenda · Porto', 'activity_agenda', 'porto-mobility-agenda.pdf', 'Sample agenda for the two-week mobility period.', now()->addDays(24)],
            ['Workshop worksheet · green logistics', 'mobility_material', 'green-logistics-workshop.pdf', 'Fictional learning material attached to the activity.', now()->addDays(27)],
            ['Mobility outcomes summary', 'mobility_output', 'mobility-outcomes-summary.pdf', 'Sample output record for the platform preview.', now()->addDays(37)],
            ['Local sharing event plan', 'dissemination_evidence', 'local-sharing-event-plan.pdf', 'Sample dissemination evidence for the coordinator.', now()->addDays(42)],
        ] as [$title, $category, $fileName, $notes, $date]) {
            ProjectDocument::query()->updateOrCreate(
                ['project_id' => $project->id, 'title' => $title],
                [
                    'type' => ProjectDocument::TYPE_UPLOAD,
                    'category' => $category,
                    'document_date' => $date->toDateString(),
                    'notes' => $notes,
                    'file_name' => $fileName,
                    'file_size' => 125000,
                    'file_disk' => 'local',
                    'metadata' => $category === 'dissemination_evidence' ? ['organisation_key' => 'oid_e10299871'] : [],
                ],
            );
        }

        CalendarEvent::query()->updateOrCreate(
            ['user_id' => $visitor->id, 'project_id' => $project->id, 'title' => 'Travel briefing'],
            ['event_date' => now()->addDays(16)->toDateString(), 'notes' => 'Fictional demo event.'],
        );

        return $project->fresh();
    }

    private static function actionData(): array
    {
        return [
            'mobility' => [
                'report' => 'The fictional mobility follows a practical green-logistics programme in Porto. Daily observations and participant reflections are stored with the activity days.',
                'photo_folder_url' => 'https://example.invalid/mobility-demo-photos',
                'photo_folder_links' => [[
                    'id' => 'demo_photo_folder',
                    'label' => 'Sample photo evidence folder',
                    'url' => 'https://example.invalid/mobility-demo-photos',
                ]],
                'final_video_url' => 'https://example.invalid/mobility-demo-video',
                'evidence_days' => [
                    ['id' => 'demo-day-1', 'title' => 'Arrival and orientation', 'date' => now()->addDays(24)->toDateString(), 'description' => 'Arrival, host-centre orientation and safety briefing.', 'observations' => 'Attendance and welcome notes reviewed.', 'links' => []],
                    ['id' => 'demo-day-2', 'title' => 'Green logistics workshop', 'date' => now()->addDays(25)->toDateString(), 'description' => 'Practical workshop on low-emission delivery planning.', 'observations' => 'Participants compared local and host-country practices.', 'links' => []],
                    ['id' => 'demo-day-3', 'title' => 'Employer visit and reflection', 'date' => now()->addDays(26)->toDateString(), 'description' => 'Visit to a local employer and guided reflection session.', 'observations' => 'Reflection prompts prepared for the group.', 'links' => []],
                ],
            ],
            'dissemination_reports' => [
                'oid_e10299871' => 'GreenTech College will share practical outcomes through a staff session, learner newsletter and local partner meeting.',
                'oid_e10321455' => 'Porto Green Skills Centre will provide a short host reflection and share selected non-personal outputs with VET partners.',
            ],
            'finalisation' => ['include' => array_fill_keys([
                'project_data', 'application', 'participants', 'budget', 'agreements', 'generated_records', 'project_files', 'mobility', 'dissemination',
            ], true)],
        ];
    }

    private static function upsertUser(string $email, string $name): User
    {
        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $name,
            'password' => Hash::make(str()->random(48)),
            'role' => User::ROLE_USER,
            'plan' => 'unlimited',
            'subscription_status' => 'active',
            'feature_flags' => ['unlimited'],
            'billing_name' => 'MobilityCloud Demo',
            'billing_country' => 'Romania',
            'billing_address' => 'Read-only sample workspace',
        ]);
        $user->email_verified_at = now();
        $user->exists && $user->trashed() ? $user->restore() : null;
        $user->save();

        return $user;
    }
}
