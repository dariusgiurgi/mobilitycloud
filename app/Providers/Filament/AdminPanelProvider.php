<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AccountSettings;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\DocumentTemplates;
use App\Filament\Pages\GlobalSearch;
use App\Filament\Pages\IndividualSupportCalculator;
use App\Filament\Pages\ManageCurrencies;
use App\Filament\Pages\ManageWorkspaceTeam;
use App\Filament\Pages\MyTasks;
use App\Filament\Pages\NotificationPreferences;
use App\Filament\Pages\PublicLibrary;
use App\Filament\Pages\Tenancy\EditWorkspaceProfile;
use App\Filament\Pages\Tenancy\RegisterWorkspace;
use App\Filament\Pages\WorkspaceCalendar;
use App\Filament\Pages\WorkspaceData;
use App\Filament\Pages\WorkspaceReports;
use App\Filament\Resources\ContentBlocks\ContentBlockResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\PublicContentBlocks\PublicContentBlockResource;
use App\Filament\Widgets\DashboardWorkspace;
use App\Filament\Widgets\ProjectStatsOverview;
use App\Http\Middleware\AuthenticateFilamentUser;
use App\Models\PlatformAnnouncement;
use App\Models\Workspace;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('app')
            ->login()
            ->registration()
            ->brandName('MobilityCloud')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling(null)
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn () => view('filament.hooks.compact-sidebar'),
            )
            ->navigationGroups([
                NavigationGroup::make('Platform management')
                    ->collapsible(false),
                NavigationGroup::make('Operations')
                    ->collapsible(false),
                NavigationGroup::make('Planning tools')
                    ->collapsible(false),
                NavigationGroup::make('Community')
                    ->collapsible(false),
                NavigationGroup::make('Workspace settings')
                    ->collapsible(false),
            ])
            ->userMenuItems([
                Action::make('accountSettings')
                    ->label('My account')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->visible(fn (): bool => Filament::getTenant() instanceof Workspace)
                    ->url(fn (): string => AccountSettings::getUrl(tenant: Filament::getTenant()))
                    ->sort(5),
            ])
            ->renderHook(
                PanelsRenderHook::TOPBAR_AFTER,
                fn () => view('filament.hooks.platform-announcements', [
                    'announcements' => auth()->check()
                        ? PlatformAnnouncement::query()
                            ->active()
                            ->latest('starts_at')
                            ->latest('created_at')
                            ->get()
                            ->filter(fn (PlatformAnnouncement $announcement): bool => $announcement->isVisibleFor(auth()->user(), Filament::getTenant()))
                            ->take(3)
                        : collect(),
                ]),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_AFTER,
                fn () => view('filament.hooks.impersonation-banner'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_AFTER,
                fn () => view('filament.hooks.subscription-access-banner'),
            )
            ->tenant(Workspace::class, slugAttribute: 'slug')
            ->tenantRegistration(RegisterWorkspace::class)
            ->tenantProfile(EditWorkspaceProfile::class)
            ->resources([
                ProjectResource::class,
                ContentBlockResource::class,
                PublicContentBlockResource::class,
            ])
            ->pages([
                Dashboard::class,
                AccountSettings::class,
                DocumentTemplates::class,
                GlobalSearch::class,
                IndividualSupportCalculator::class,
                ManageCurrencies::class,
                ManageWorkspaceTeam::class,
                MyTasks::class,
                NotificationPreferences::class,
                PublicLibrary::class,
                WorkspaceCalendar::class,
                WorkspaceData::class,
                WorkspaceReports::class,
            ])
            ->widgets([
                ProjectStatsOverview::class,
                DashboardWorkspace::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateFilamentUser::class,
            ]);
    }
}
