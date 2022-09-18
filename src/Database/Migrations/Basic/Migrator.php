<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Migrations\Basic;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Info;
use Illuminate\Console\View\Components\Warn;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\File;
use LogicException;
use SplFileInfo;
use StepUpDream\DreamAbility\Database\Migrations\Migration;
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
     * @var MigrationRepositoryInterface[]
     */
    protected array $migrationRepositories = [];

    /**
     * @param  \StepUpDream\DreamAbilitySupport\Supports\File\FileOperation  $fileOperation
     * @param  \Illuminate\Database\DatabaseManager  $databaseManager
     */
    public function __construct(
        protected FileOperation $fileOperation,
        protected DatabaseManager $databaseManager
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
     * @param  string[]  $connectionNames
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
        $migrationRepository = $this->migrationRepository($migration->connectionName());
        $connection = $migrationRepository->getConnection();

        if (! method_exists($migration, 'up')) {
            throw new LogicException("Specify 'up' in the method : ".$file->getRealPath());
        }

        // Already processed migrations shall not be duplicated.
        $beforeVersion = $migrationRepository->findVersionByTableName($migration->tableName());
        if ($version <= $beforeVersion) {
            return 'SKIP';
        }

        $callback = function () use ($migration) {
            $migration->up();
        };

        $this->useTransactions($connection, $migration) ? $connection->transaction($callback) : $callback();

        $this->log($migration->connectionName(), $migration->tableName(), $version);

        return 'DONE';
    }

    /**
     * Whether to use transactions.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  \StepUpDream\DreamAbility\Database\Migrations\Migration  $migration
     * @return bool
     */
    protected function useTransactions(Connection $connection, Migration $migration): bool
    {
        return $connection->getSchemaGrammar()->supportsSchemaTransactions() && $migration->withinTransaction;
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
     * Determine if the migration table exists.
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
     * @param  string[]  $columns
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
     * @return \StepUpDream\DreamAbility\Database\Migrations\Basic\MigrationRepositoryInterface
     */
    protected function migrationRepository(string $connectionName): mixed
    {
        if (isset($this->migrationRepositories[$connectionName])) {
            return $this->migrationRepositories[$connectionName];
        }

        $this->migrationRepositories[$connectionName] = $this->makeMigrationRepository($connectionName);

        return $this->migrationRepositories[$connectionName];
    }

    /**
     * Make repository to manipulate version control tables.
     *
     * @param  string  $connectionName
     * @return MigrationRepositoryInterface
     */
    protected function makeMigrationRepository(string $connectionName): MigrationRepositoryInterface
    {
        return app(MigrationRepositoryInterface::class, [$connectionName]);
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
        $migrationRepository = $this->migrationRepository($connectionName);
        if (! $this->repositoryExists($connectionName)) {
            throw new LogicException('The table for version control does not exist.');
        }

        $result = $migrationRepository->existTargetVersion($tableName, $version);
        if (empty($result)) {
            return;
        }

        $migrationRepository->insert($tableName, $version);
    }
}
