<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\FinancialWorkflow::class);
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([\Illuminate\Auth\Events\Login::class => 'login', \Illuminate\Auth\Events\Logout::class => 'logout'] as $event => $action) {
            \Illuminate\Support\Facades\Event::listen($event, function ($event) use ($action) {
                if ($event->user) \App\Models\AuditLog::create(['user_id' => $event->user->id, 'target_type' => \App\Models\User::class, 'target_id' => $event->user->id, 'aksi' => $action]);
            });
        }
        foreach (config('bos-modules', []) as $module) {
            \Illuminate\Support\Facades\Gate::policy($module['model'], \App\Policies\BosPolicy::class);
        }
        //
    }
}
