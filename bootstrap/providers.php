<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\DemoPanelProvider;
use App\Providers\Filament\PlatformPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    DemoPanelProvider::class,
    PlatformPanelProvider::class,
];
