<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Migrations\Basic;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Info;
use Illuminate\Console\View\Components\Warn;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Facades\File;
use LogicException;
use SplFileInfo;
use StepUpDream\DreamAbilitySupport\Console\View\Components\Task;
use StepUpDream\DreamAbilitySupport\Supports\File\FileOperation;

class Migrator
{
    /**
     * The output style implementation.
     *
     * @var \Illuminate\Console\OutputStyle
     */
    protected OutputStyle $output;

    /**
     * Repository to manipulate version control tables. [key: connection name]
     *
     * @var MigrationRepository[]
     */
    protected array $migrationRepositories = [];

    /**
     * @param  \StepUpDream\DreamAbilitySupport\Supports\File\FileOperation  $fileOperation
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     */
    public function __construct(
        protected FileOperation $fileOperation,
        protected Resolver $resolver
    ) {
    }

    /**
     * Migrations execution.
     *
     * @param  string  $migrationDirectoryPath
     */
    public function migration(string $migrationDirectoryPath): void
    {
        $version = basename($migrationDirectoryPath);
        $files = $this->fileOperation->allFiles($migrationDirectoryPath);

        (new Info($this->output))->render(sprintf('Version %s : running migration', $version));

        foreach ($files as $file) {
            $basename = basename($file->getRealPath());
            (new Task($this->output))->render($basename, fn () => $this->runUp($version, $file));
        }

        $this->output->newLine();
    }

    /**
     * Warn if a migration has been performed in the past but the migration file does not exist.
     *
     * @param  array  $connectionNames
     * @param  string  $migrationDirectoryPath
     */
    public function verifyMigration(array $connectionNames, string $migrationDirectoryPath): void
    {
        $usedTable = [];
        $version = basename($migrationDirectoryPath);
        foreach ($connectionNames as $connectionName) {
            $usedTable[] = $this->migrationRepository($connectionName)->getTableNameByVersion($version);
        }
        $usedTable = array_merge(...$usedTable);

        $useTable = [];
        $files = $this->fileOperation->allFiles($migrationDirectoryPath);
        foreach ($files as $file) {
            /** @var \StepUpDream\DreamAbility\Database\Migrations\Migration $migration */
            $migration = File::getRequire($file->getRealPath());
            $useTable[] = $migration->tableName();
        }
        sort($useTable);
        sort($usedTable);

        if ($useTable !== $usedTable) {
            (new Warn($this->output))->render(
                sprintf('Version %s : Version information does not match between DB and migration file.', $version)
            );
        }
    }

    /**
     * Run "up" a migration instance.
     *
     * @param  string  $version
     * @param  \Symfony\Component\Finder\SplFileInfo  $file
     * @return string
     *
     * @see \Illuminate\Database\Migrations\Migrator::runUp()
     */
    protected function runUp(string $version, SplFileInfo $file): string
    {
        /** @var \StepUpDream\DreamAbility\Database\Migrations\Migration $migration */
        $migration = File::getRequire($file->getRealPath());
        $connection = $this->resolveConnection($migration->connectionName());

        if (! method_exists($migration, 'up')) {
            throw new LogicException("Specify 'up' in the method : ".$file->getRealPath());
        }

        // Already processed migrations shall not be duplicated.
        $migrationRepository = $this->migrationRepository($migration->connectionName());
        $beforeVersion = $migrationRepository->findVersionByTableName($migration->tableName());
        if ($version <= $beforeVersion) {
            return 'SKIP';
        }

        $callback = function () use ($migration) {
            $migration->up();
        };

        $this->getSchemaGrammar($connection)->supportsSchemaTransactions() && $migration->withinTransaction
            ? $connection->transaction($callback)
            : $callback();

        $this->log($migration->connectionName(), $migration->tableName(), $version);

        return 'DONE';
    }

    /**
     * Get the schema grammar out of a migration connection.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     *
     * @see \Illuminate\Database\Migrations\Migrator::getSchemaGrammar()
     */
    protected function getSchemaGrammar(Connection $connection): Grammar
    {
        $grammar = $connection->getSchemaGrammar();
        if ($grammar === null) {
            $connection->useDefaultSchemaGrammar();

            $grammar = $connection->getSchemaGrammar();
        }

        return $grammar;
    }

    /**
     * Resolve the database connection instance.
     *
     * @param  string  $connection
     * @return \Illuminate\Database\ConnectionInterface|\Illuminate\Database\Connection
     */
    protected function resolveConnection(string $connection): ConnectionInterface|Connection
    {
        return $this->resolver->connection($connection);
    }

    /**
     * Set the output implementation that should be used by the console.
     *
     * @param  \Illuminate\Console\OutputStyle  $output
     * @return $this
     */
    public function setOutput(OutputStyle $output): static
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Determine if the migration repository exists.
     *
     * @param  string  $connectionName
     * @return bool
     */
    public function repositoryExists(string $connectionName): bool
    {
        return $this->migrationRepository($connectionName)->repositoryExists();
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param  string  $connectionName
     * @param  array  $columns
     * @return bool
     */
    public function repositoryColumnExists(string $connectionName, array $columns): bool
    {
        return $this->migrationRepository($connectionName)->hasColumns($columns);
    }

    /**
     * Max version.
     *
     * @param  string  $connectionName
     * @return string
     */
    public function repositoryMaxVersion(string $connectionName): string
    {
        return $this->migrationRepository($connectionName)->maxVersion();
    }

    /**
     * Get repository to manipulate version control tables.
     *
     * @param  string  $connectionName
     * @return \StepUpDream\DreamAbility\Database\Migrations\Basic\MigrationRepository
     */
    protected function migrationRepository(string $connectionName): MigrationRepository
    {
        if (isset($this->migrationRepositories[$connectionName])) {
            return $this->migrationRepositories[$connectionName];
        }

        $tableName = config('stepupdream.migration.basic.version_control_table_name');
        $migrationRepository = new MigrationRepository($connectionName, $this->resolver, $tableName);
        $this->migrationRepositories[$connectionName] = $migrationRepository;

        return $this->migrationRepositories[$connectionName];
    }

    /**
     * Log that a migration was run.
     *
     * @param  string  $connectionName
     * @param  string  $tableName
     * @param  string  $version
     * @return void
     */
    protected function log(string $connectionName, string $tableName, string $version): void
    {
        $this->migrationRepository($connectionName)->log($tableName, $version);
    }
}
