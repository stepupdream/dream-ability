<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Console\Migrations\Basic;

use Illuminate\Support\Facades\File;
use LogicException;
use StepUpDream\DreamAbility\Database\Console\Migrations\BaseMigrationCommand;
use StepUpDream\DreamAbility\Database\Migrations\Basic\Migrator;
use StepUpDream\DreamAbilitySupport\Supports\File\FileOperation;

class MigrateCommand extends BaseMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dream-ability-migrate:migrate-basic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database migrations';

    /**
     * Obtain the connection name list to be use.
     *
     * @var string[]
     */
    protected array $useConnectionNames;

    /**
     * Create a new console command instance.
     *
     * @param  \StepUpDream\DreamAbilitySupport\Supports\File\FileOperation  $fileOperation
     * @param  \StepUpDream\DreamAbility\Database\Migrations\Basic\Migrator  $migrator
     */
    public function __construct(
        protected FileOperation $fileOperation,
        protected Migrator $migrator
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $migrationDirectoryParentPath = config('stepupdream.migration.basic.migration_directory_parent_path');

        $this->verifySameFileNameExist();
        $this->prepareDatabase();
        $maxVersion = $this->maxVersion();
        $usedConnectionName = $this->usedConnectionName();

        $migrationDirectories = File::directories($migrationDirectoryParentPath);
        $migrationDirectories = collect($migrationDirectories)->sort()->all();
        foreach ($migrationDirectories as $migrationDirectoryPath) {
            $version = basename($migrationDirectoryPath);
            if ($maxVersion <= $version) {
                $this->migrator->setOutput($this->output)->migration($migrationDirectoryPath);
            }
            $this->migrator->setOutput($this->output)->verifyMigration($usedConnectionName, $migrationDirectoryPath);
        }

        $this->commandDetailLog('command run detail');
    }

    /**
     * Whether a file with the same filename exists or not.
     */
    protected function verifySameFileNameExist(): void
    {
        $definitionDatabaseDirectoryPath = config('stepupdream.migration.basic.definition_database_directory_path');

        if ($this->fileOperation->isSameFileNameExist($definitionDatabaseDirectoryPath)) {
            throw new LogicException('Avoid naming tables the same. : '.$definitionDatabaseDirectoryPath);
        }
    }

    /**
     * Prepare the migration database for running.
     *
     * @return void
     */
    protected function prepareDatabase(): void
    {
        $callbacks = [];
        $connectionNames = $this->useConnectionNames();

        foreach ($connectionNames as $connectionName) {
            if (! $this->repositoryExists($connectionName)) {
                $callbacks[] = fn () => $this->migrate($connectionName);
            }
        }

        if (empty($callbacks)) {
            return;
        }

        $this->components->info('Preparing database.');
        foreach ($callbacks as $callback) {
            $callback();
        }
        $this->newLine();
    }

    /**
     * Run "dream-ability-migrate:install-basic".
     *
     * @param  string  $connectionName
     * @return void
     */
    protected function migrate(string $connectionName): void
    {
        $this->components->task('Creating migration table', function () use ($connectionName) {
            return $this->callSilent('dream-ability-migrate:install-basic', ['name' => $connectionName]) === 0;
        });
    }

    /**
     * Obtain the connection name list to be use.
     *
     * @return string[]
     */
    protected function useConnectionNames(): array
    {
        if (isset($this->useConnectionNames)) {
            return $this->useConnectionNames;
        }

        $connectionNames = [];
        $migrationDirectoryParentPath = config('stepupdream.migration.basic.migration_directory_parent_path');
        $migrationFiles = $this->fileOperation->allFiles($migrationDirectoryParentPath);

        foreach ($migrationFiles as $migrationFile) {
            /** @var \StepUpDream\DreamAbility\Database\Migrations\Migration $migration */
            $migration = File::getRequire($migrationFile->getRealPath());
            $connectionName = $migration->connectionName();
            $connectionNames[$connectionName] = $connectionName;
        }

        foreach ($connectionNames as $connectionName) {
            $canUseConnection = config('database.connections.'.$connectionName);
            if (! $canUseConnection) {
                throw new LogicException("Connection not specified in 'config.database.php' : ".$connectionName);
            }
        }

        $this->useConnectionNames = array_values($connectionNames);

        return $this->useConnectionNames;
    }

    /**
     * Get used connection.
     *
     * Obtain a list of connection names actually in use among the databases defined
     * in 'database.php' and 'migration.php
     *
     * @return string[]
     */
    protected function usedConnectionName(): array
    {
        $excludeConnections = config('stepupdream.migration.basic.exclude_connections');
        $connections = config('database.connections');
        $connectionNames = [];

        foreach ($connections as $connectionName => $connection) {
            if (! in_array($connectionName, $excludeConnections, true) &&
                $this->migrator->repositoryColumnExists($connectionName, ['table_name', 'version'])
            ) {
                $connectionNames[] = $connectionName;
            }
        }

        return $connectionNames;
    }

    /**
     * Determine if the migration repository exists.
     *
     * @param  string  $connectionName
     * @return bool
     */
    public function repositoryExists(string $connectionName): bool
    {
        return $this->migrator->repositoryExists($connectionName);
    }

    /**
     * Max version.
     *
     * @return string
     */
    public function maxVersion(): string
    {
        $maxVersion = '';
        $connectionNames = $this->useConnectionNames();
        foreach ($connectionNames as $connectionName) {
            $version = $this->migrator->repositoryMaxVersion($connectionName);
            if ($maxVersion === '' || $maxVersion < $version) {
                $maxVersion = $version;
            }
        }

        return $maxVersion;
    }
}
