<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\HtmlString;
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
            ->path('admin')
            ->login()
            ->profile()
            ->databaseTransactions()
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->maxContentWidth(\Filament\Support\Enums\Width::Full)
            ->navigationGroups(['MENU UTAMA', 'DANA BOS', 'MONITORING', 'PENGATURAN'])
            ->brandName('Monitoring BOS SMP Sangihe')
            ->brandLogo(fn () => new HtmlString(view('filament.brand')->render()))
            ->brandLogoHeight('2.75rem')
            ->renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_START, fn () => view('filament.sidebar-identity'))
            ->renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_FOOTER, fn () => view('filament.sidebar-footer'))
            ->renderHook(\Filament\View\PanelsRenderHook::CONTENT_START, fn () => view('filament.demo-banner'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors(['primary' => Color::Teal])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
            ->authMiddleware([Authenticate::class]);
    }
}
