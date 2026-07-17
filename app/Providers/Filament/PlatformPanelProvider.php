<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AccountSettings;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\PlatformBillingOperations;
use App\Filament\Pages\PlatformHealth;
use App\Filament\Pages\PlatformPermissions;
use App\Filament\Resources\PlatformActivities\PlatformActivityResource;
use App\Filament\Resources\PlatformAnnouncements\PlatformAnnouncementResource;
use App\Filament\Resources\PlatformAuditLogs\PlatformAuditLogResource;
use App\Filament\Resources\PlatformProjectPayments\PlatformProjectPaymentResource;
use App\Filament\Resources\PlatformUsers\PlatformUserResource;
use App\Filament\Resources\PublicBlockReports\PublicBlockReportResource;
use App\Http\Middleware\AuthenticateFilamentUser;
use App\Http\Middleware\RedirectPlatformLoginToUnifiedLogin;
use App\Models\PlatformAnnouncement;
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

class PlatformPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('platform')
            ->path('platform')
            ->login()
            ->passwordReset()
            ->brandName('MobilityCloud Platform')
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
                NavigationGroup::make('Billing & access')
                    ->collapsible(false),
                NavigationGroup::make('Audit & operations')
                    ->collapsible(false),
            ])
            ->userMenuItems([
                Action::make('accountSettings')
                    ->label('My account')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->url(fn (): string => AccountSettings::getUrl(panel: 'platform'))
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
                            ->filter(fn (PlatformAnnouncement $announcement): bool => $announcement->isVisibleFor(auth()->user(), null))
                            ->take(3)
                        : collect(),
                ]),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_AFTER,
                fn () => view('filament.hooks.platform-role-badge'),
            )
            ->pages([
                Dashboard::class,
                AccountSettings::class,
                PlatformBillingOperations::class,
                PlatformPermissions::class,
                PlatformHealth::class,
            ])
            ->resources([
                PlatformUserResource::class,
                PlatformProjectPaymentResource::class,
                PlatformActivityResource::class,
                PlatformAnnouncementResource::class,
                PublicBlockReportResource::class,
                PlatformAuditLogResource::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                RedirectPlatformLoginToUnifiedLogin::class,
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
