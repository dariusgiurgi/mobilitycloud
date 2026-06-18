<?php

namespace App\Filament\Resources\PublicBlockReports;

use App\Filament\Resources\PublicBlockReports\Pages\ListPublicBlockReports;
use App\Filament\Resources\PublicBlockReports\Tables\PublicBlockReportsTable;
use App\Models\PublicBlockReport;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PublicBlockReportResource extends Resource
{
    protected static ?string $model = PublicBlockReport::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;
    protected static string|\UnitEnum|null $navigationGroup = 'Community';
    protected static ?string $navigationLabel = 'Reports';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'report';
    protected static ?string $pluralModelLabel = 'reports';

    // Nu e tenant-scoped: raportarile sunt globale.
    public static function isScopedToTenant(): bool
    {
        return false;
    }

    // Vizibila in sidebar DOAR pentru moderatori.
    public static function shouldRegisterNavigation(): bool
    {
        return Filament::auth()->user()?->canModerate() ?? false;
    }

    // Blocheaza accesul direct la URL pentru non-moderatori.
    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->canModerate() ?? false;
    }

    // Badge cu numarul de raportari in asteptare.
    public static function getNavigationBadge(): ?string
    {
        $count = PublicBlockReport::where('status', PublicBlockReport::STATUS_PENDING)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return PublicBlockReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPublicBlockReports::route('/'),
        ];
    }
}