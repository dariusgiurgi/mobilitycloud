<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'plan')) {
                $table->string('plan')->default('free')->after('role');
            }
            if (! Schema::hasColumn('users', 'subscription_status')) {
                $table->string('subscription_status')->default('active')->after('plan');
            }
            if (! Schema::hasColumn('users', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            }
            if (! Schema::hasColumn('users', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            }
            if (! Schema::hasColumn('users', 'feature_flags')) {
                $table->json('feature_flags')->nullable()->after('subscription_ends_at');
            }
            if (! Schema::hasColumn('users', 'plan_limits')) {
                $table->json('plan_limits')->nullable()->after('feature_flags');
            }
            if (! Schema::hasColumn('users', 'currencies')) {
                $table->json('currencies')->nullable()->after('plan_limits');
            }
            if (! Schema::hasColumn('users', 'document_settings')) {
                $table->json('document_settings')->nullable()->after('currencies');
            }
            if (! Schema::hasColumn('users', 'access_override_ends_at')) {
                $table->timestamp('access_override_ends_at')->nullable()->after('document_settings');
            }
            if (! Schema::hasColumn('users', 'access_override_reason')) {
                $table->string('access_override_reason')->nullable()->after('access_override_ends_at');
            }
        });

        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
        });

        DB::table('users')
            ->leftJoin('workspace_user', function ($join): void {
                $join->on('workspace_user.user_id', '=', 'users.id')
                    ->where('workspace_user.role', '=', 'owner');
            })
            ->leftJoin('workspaces', 'workspaces.id', '=', 'workspace_user.workspace_id')
            ->whereNotNull('workspaces.id')
            ->orderBy('workspaces.created_at')
            ->select([
                'users.id as user_id',
                'workspaces.plan',
                'workspaces.subscription_status',
                'workspaces.trial_ends_at',
                'workspaces.subscription_ends_at',
                'workspaces.feature_flags',
                'workspaces.plan_limits',
                'workspaces.currencies',
                'workspaces.document_settings',
                'workspaces.access_override_ends_at',
                'workspaces.access_override_reason',
            ])
            ->get()
            ->each(function ($row): void {
                DB::table('users')->where('id', $row->user_id)->update([
                    'plan' => $row->plan ?: 'free',
                    'subscription_status' => $row->subscription_status ?: 'active',
                    'trial_ends_at' => $row->trial_ends_at,
                    'subscription_ends_at' => $row->subscription_ends_at,
                    'feature_flags' => $row->feature_flags,
                    'plan_limits' => $row->plan_limits,
                    'currencies' => $row->currencies,
                    'document_settings' => $row->document_settings,
                    'access_override_ends_at' => $row->access_override_ends_at,
                    'access_override_reason' => $row->access_override_reason,
                ]);
            });

        DB::table('projects')
            ->whereNull('owner_id')
            ->select(['id', 'workspace_id'])
            ->orderBy('id')
            ->get()
            ->each(function ($project): void {
                $ownerId = DB::table('workspace_user')
                    ->where('workspace_id', $project->workspace_id)
                    ->where('role', 'owner')
                    ->orderBy('user_id')
                    ->value('user_id');

                if ($ownerId) {
                    DB::table('projects')->where('id', $project->id)->update(['owner_id' => $ownerId]);
                }
            });

        DB::table('projects')
            ->whereNull('owner_id')
            ->update(['owner_id' => DB::table('users')->min('id')]);

        Schema::table('projects', function (Blueprint $table): void {
            try {
                $table->dropForeign(['workspace_id']);
            } catch (Throwable) {
                //
            }
        });

        Schema::table('workspace_invitations', function (Blueprint $table): void {
            try {
                $table->dropForeign(['workspace_id']);
            } catch (Throwable) {
                //
            }
        });

        Schema::table('projects', function (Blueprint $table): void {
            if (Schema::hasColumn('projects', 'workspace_id')) {
                $table->unsignedBigInteger('workspace_id')->nullable()->change();
            }
        });

        Schema::table('workspace_invitations', function (Blueprint $table): void {
            if (Schema::hasColumn('workspace_invitations', 'workspace_id')) {
                $table->unsignedBigInteger('workspace_id')->nullable()->change();
            }
        });

        Schema::table('project_activity_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('project_activity_logs', 'workspace_id')) {
                try {
                    $table->dropForeign(['workspace_id']);
                } catch (Throwable) {
                    //
                }

                $table->unsignedBigInteger('workspace_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (Schema::hasColumn('projects', 'owner_id')) {
                $table->dropConstrainedForeignId('owner_id');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'access_override_reason',
                'access_override_ends_at',
                'document_settings',
                'currencies',
                'plan_limits',
                'feature_flags',
                'subscription_ends_at',
                'trial_ends_at',
                'subscription_status',
                'plan',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
