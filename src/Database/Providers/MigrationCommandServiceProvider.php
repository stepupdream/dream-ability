<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use StepUpDream\DreamAbility\Database\Console\Migrations\MigrationCommand;

class MigrationCommandServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected array $commands = [
        'CommandMigrationRun' => 'command.migration.run',
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

            $this->app->singleton('command.migration.run', function () {
                return $this->app->make(MigrationCommand::class);
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
