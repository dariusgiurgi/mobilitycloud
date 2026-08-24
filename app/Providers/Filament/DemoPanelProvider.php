<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\GlobalSearch;
use App\Filament\Pages\MyTasks;
use App\Filament\Pages\ProjectCalendar;
use App\Filament\Resources\Projects\ProjectResource;
use App\Http\Middleware\AuthenticateFilamentUser;
use App\Http\Middleware\RejectDemoWriteRequests;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DemoPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('demo')
            ->path('demo')
            ->authGuard('demo')
            ->brandName('MobilityCloud')
            ->brandLogo(asset('brand/mobilitycloud-logo-horizontal.png'))
            ->darkModeBrandLogo(asset('brand/mobilitycloud-logo-horizontal.png'))
            ->brandLogoHeight('2.25rem')
            ->homeUrl(url('/'))
            ->favicon(asset('favicon.ico'))
            ->colors(['primary' => Color::hex('#1677ff')])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling(null)
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => view('filament.hooks.compact-sidebar'))
            ->renderHook(PanelsRenderHook::TOPBAR_AFTER, fn () => view('filament.hooks.demo-read-only-banner'))
            ->navigationGroups([
                NavigationGroup::make('Platform management')->collapsible(false),
                NavigationGroup::make('Operations')->collapsible(false),
                NavigationGroup::make('Planning tools')->collapsible(false),
                NavigationGroup::make('Community')->collapsible(false),
                NavigationGroup::make('Account settings')->collapsible(false),
            ])
            ->resources([ProjectResource::class])
            ->pages([
                Dashboard::class,
                GlobalSearch::class,
                MyTasks::class,
                ProjectCalendar::class,
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
                RejectDemoWriteRequests::class,
            ])
            ->authMiddleware([AuthenticateFilamentUser::class]);
    }
}
