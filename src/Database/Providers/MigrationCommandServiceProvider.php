<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use StepUpDream\DreamAbility\Database\Console\Migrations\Basic\InstallCommand;
use StepUpDream\DreamAbility\Database\Console\Migrations\Basic\MigrateCommand;
use StepUpDream\DreamAbility\Database\Migrations\Basic\MigrationRepository;
use StepUpDream\DreamAbility\Database\Migrations\Basic\MigrationRepositoryInterface;

class MigrationCommandServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The commands to be registered.
     *
     * @var array<int, string>
     */
    protected array $commands = [
        1 => 'command.migrate.migrate-basic',
        2 => 'command.migrate.install-basic',
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

            $this->app->singleton($this->commands[1], function () {
                return $this->app->make(MigrateCommand::class);
            });

            $this->app->singleton($this->commands[2], function () {
                return $this->app->make(InstallCommand::class);
            });

            $this->commands(array_values($this->commands));

            $this->app->bind(MigrationRepositoryInterface::class, function ($app, $parameters) {
                $tableName = config('stepupdream.migration.basic.version_control_table_name');

                return new MigrationRepository($parameters[0], $app['db'], $tableName);
            });
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides(): array
    {
        return [
            $this->commands[1],
            $this->commands[2],
            MigrationRepositoryInterface::class,
        ];
    }
}
