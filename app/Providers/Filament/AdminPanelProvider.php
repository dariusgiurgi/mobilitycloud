<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AccountSettings;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Tenancy\EditWorkspaceProfile;
use App\Filament\Pages\Tenancy\RegisterWorkspace;
use App\Models\PlatformAnnouncement;
use App\Models\Workspace;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
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
            ->databaseNotificationsPolling('30s')
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
                    ->url(fn (): string => AccountSettings::getUrl())
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
            ->tenant(Workspace::class, slugAttribute: 'slug')
            ->tenantRegistration(RegisterWorkspace::class)
            ->tenantProfile(EditWorkspaceProfile::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
                Authenticate::class,
            ]);
    }
}
