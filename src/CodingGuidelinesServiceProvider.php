<?php

declare(strict_types=1);

namespace CodebarAg\CodingGuidelines;

use CodebarAg\CodingGuidelines\Console\SyncRefactorCommand;
use Illuminate\Support\ServiceProvider;

class CodingGuidelinesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Skills are auto-discovered by Laravel Boost from resources/boost/skills/
        // No registration needed — Boost v2.2+ reads vendor/{pkg}/resources/boost/skills/
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncRefactorCommand::class,
            ]);
        }
    }
}
