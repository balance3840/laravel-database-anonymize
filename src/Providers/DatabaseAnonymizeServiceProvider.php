<?php

namespace RamiroEstrella\LaravelDatabaseAnonymize\Providers;

use Illuminate\Support\ServiceProvider;
use RamiroEstrella\LaravelDatabaseAnonymize\Console\Commands\AnonymizeCommand;

final class DatabaseAnonymizeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__ . '/../../config/database-anonymize.php' => config_path('database-anonymize.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AnonymizeCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        // Merge default configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/database-anonymize.php',
            'database-anonymize'
        );
    }
}
