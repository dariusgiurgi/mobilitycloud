<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PlatformActivities\PlatformActivityResource;
use App\Filament\Resources\PlatformAnnouncements\PlatformAnnouncementResource;
use App\Filament\Pages\PlatformHealth;
use App\Filament\Pages\PlatformBillingOperations;
use App\Filament\Pages\PlatformPermissions;
use App\Filament\Resources\PlatformProjectPayments\PlatformProjectPaymentResource;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Resources\PublicBlockReports\PublicBlockReportResource;
use App\Models\PlatformAuditLog;
use App\Models\PublicBlockReport;
use App\Models\User;
use App\Support\AccountAccess;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlatformOperationsOverview extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.platform-operations-overview';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'recentUsers' => User::query()->latest()->limit(5)->get(),
            'endingSoon' => User::query()
                ->where(function ($query): void {
                    $query
                        ->where(function ($query): void {
                            $query
                                ->whereNotNull('subscription_ends_at')
                                ->where('subscription_ends_at', '<=', now()->addDays(30));
                        })
                        ->orWhere('is_suspended', true)
                        ->orWhere('subscription_status', 'expired');
                })
                ->orderByRaw('COALESCE(subscription_ends_at, trial_ends_at, created_at) asc')
                ->limit(5)
                ->get(),
            'recentAudit' => PlatformAuditLog::query()->with('actor')->latest()->limit(6)->get(),
            'alerts' => $this->alerts(),
            'attentionItems' => $this->attentionItems(),
            'linkGroups' => [
                [
                    'label' => 'Platform management',
                    'links' => [
                        ['label' => 'Accounts', 'url' => PlatformUserResource::getUrl(), 'detail' => 'Users, roles, password resets'],
                        ['label' => 'Announcements', 'url' => PlatformAnnouncementResource::getUrl(), 'detail' => 'Header notices and incidents'],
                        ['label' => 'Moderation reports', 'url' => PublicBlockReportResource::getUrl(), 'detail' => 'Review public content reports'],
                    ],
                ],
                [
                    'label' => 'Billing & access',
                    'links' => [
                        ['label' => 'Billing operations', 'url' => PlatformBillingOperations::getUrl(), 'detail' => 'What to invoice, chase or clean up today'],
                        ['label' => 'Project payments', 'url' => PlatformProjectPaymentResource::getUrl(), 'detail' => 'Approved grants, invoices and payment unlocks'],
                        ['label' => 'Account access', 'url' => PlatformUserResource::getUrl(), 'detail' => 'Standard/unlimited access and billing details'],
                    ],
                ],
                [
                    'label' => 'Audit & operations',
                    'links' => [
                        ['label' => 'Activity center', 'url' => PlatformActivityResource::getUrl(), 'detail' => 'Audited admin actions'],
                        ['label' => 'Permissions matrix', 'url' => PlatformPermissions::getUrl(), 'detail' => 'Owner/admin capabilities'],
                        ['label' => 'System health', 'url' => PlatformHealth::getUrl(), 'detail' => 'Storage, queue, mail and jobs'],
                    ],
                ],
            ],
        ];
    }

    protected function alerts(): array
    {
        return [
            [
                'label' => 'Read-only accounts',
                'count' => User::query()->get()->filter(fn (User $user): bool => AccountAccess::isReadOnly($user))->count(),
                'detail' => 'Expired or suspended access',
            ],
            [
                'label' => 'Unlimited accounts',
                'count' => User::query()->get()->filter(fn (User $user): bool => $user->isUnlimitedAccount())->count(),
                'detail' => 'Full access granted by platform owner',
            ],
            [
                'label' => 'Recent impersonations',
                'count' => PlatformAuditLog::query()->where('action', 'impersonation.started')->where('created_at', '>=', now()->subDays(7))->count(),
                'detail' => 'Support access sessions in 7 days',
            ],
        ];
    }

    protected function attentionItems(): array
    {
        $items = [
            [
                'label' => 'Suspended accounts',
                'count' => User::query()->where('is_suspended', true)->count(),
                'detail' => 'Users blocked from client modules',
                'url' => PlatformUserResource::getUrl(),
                'level' => 'danger',
            ],
            [
                'label' => 'Archived accounts',
                'count' => User::query()->whereNotNull('archived_at')->count(),
                'detail' => 'Accounts kept for recovery/review',
                'url' => PlatformUserResource::getUrl(),
                'level' => 'gray',
            ],
            [
                'label' => 'Expired or suspended accounts',
                'count' => User::query()
                    ->where(function ($query): void {
                        $query
                            ->where('is_suspended', true)
                            ->orWhereIn('subscription_status', ['expired', 'suspended'])
                            ->orWhere(function ($query): void {
                                $query
                                    ->whereNotNull('subscription_ends_at')
                                    ->where('subscription_ends_at', '<', now());
                            });
                    })
                    ->count(),
                'detail' => 'Accounts with blocked or ended access',
                'url' => PlatformUserResource::getUrl(),
                'level' => 'danger',
            ],
            [
                'label' => 'Pending moderation reports',
                'count' => PublicBlockReport::query()->where('status', PublicBlockReport::STATUS_PENDING)->count(),
                'detail' => 'Public library content waiting for review',
                'url' => PublicBlockReportResource::getUrl(),
                'level' => 'warning',
            ],
            [
                'label' => 'Failed jobs',
                'count' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
                'detail' => 'Background jobs needing technical review',
                'url' => PlatformHealth::getUrl(),
                'level' => 'warning',
            ],
        ];

        return collect($items)
            ->filter(fn (array $item): bool => $item['count'] > 0)
            ->values()
            ->all();
    }
}
