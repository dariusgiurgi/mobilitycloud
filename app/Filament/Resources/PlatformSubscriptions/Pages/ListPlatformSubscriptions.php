<?php

namespace App\Filament\Resources\PlatformSubscriptions\Pages;

use App\Filament\Resources\PlatformSubscriptions\PlatformSubscriptionResource;
use App\Filament\Widgets\PlatformSubscriptionsOverview;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPlatformSubscriptions extends ListRecords
{
    protected static string $resource = PlatformSubscriptionResource::class;

    public function getSubheading(): ?string
    {
        return 'Subscription command center for trials, demo workspaces, manual access and accounts needing attention.';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PlatformSubscriptionsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'attention' => Tab::make('Needs attention')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                    $query
                        ->where('is_suspended', true)
                        ->orWhereIn('subscription_status', ['expired', 'suspended'])
                        ->orWhere(function (Builder $query): void {
                            $query
                                ->whereNotNull('subscription_ends_at')
                                ->where('subscription_ends_at', '<=', now()->addDays(14));
                        })
                        ->orWhere(function (Builder $query): void {
                            $query
                                ->where('subscription_status', 'trial')
                                ->whereNotNull('trial_ends_at')
                                ->where('trial_ends_at', '<=', now()->addDays(7));
                        });
                })),
            'trial' => Tab::make('Trial')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('subscription_status', 'trial')),
            'demo' => Tab::make('Demo')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where(fn (Builder $query): Builder => $query
                        ->where('plan', 'demo')
                        ->orWhere('subscription_status', 'demo'))),
            'manual_access' => Tab::make('Manual access')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->whereNotNull('access_override_reason')
                    ->where(function (Builder $query): void {
                        $query
                            ->whereNull('access_override_ends_at')
                            ->orWhere('access_override_ends_at', '>', now());
                    })),
            'blocked' => Tab::make('Expired / suspended')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where(function (Builder $query): void {
                        $query
                            ->where('is_suspended', true)
                            ->orWhereIn('subscription_status', ['expired', 'suspended'])
                            ->orWhere(function (Builder $query): void {
                                $query
                                    ->whereNotNull('subscription_ends_at')
                                    ->where('subscription_ends_at', '<', now());
                            });
                    })),
        ];
    }
}
