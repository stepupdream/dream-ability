<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use StepUpDream\DreamAbility\Database\Console\Migrations\Basic\InstallCommand;
use StepUpDream\DreamAbility\Database\Console\Migrations\Basic\MigrateCommand;

class MigrationCommandServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected array $commands = [
        'MigrateBasicRun' => 'command.migrate.migrate-basic',
        'InstallBasicRun' => 'command.migrate.install-basic',
    ];

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->mergeConfigFrom(__DIR__.'/../Config/stepupdream/migration.php', 'stepupdream.migration');

            $this->publishes([
                __DIR__.'/../Config/stepupdream/migration.php' => config_path('stepupdream/migration.php'),
            ], 'dream-ability');

            $this->app->singleton('command.migrate.migrate-basic', function () {
                return $this->app->make(MigrateCommand::class);
            });

            $this->app->singleton('command.migrate.install-basic', function () {
                return $this->app->make(InstallCommand::class);
            });

            $this->commands(array_values($this->commands));
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return array_values($this->commands);
    }
}
