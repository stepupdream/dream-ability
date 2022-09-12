<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Migrations\Basic;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Info;
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
     * MigrationRevisionManager constructor.
     *
     * @param  \StepUpDream\DreamAbility\Database\Migrations\Basic\MigrationRepository  $migrationRepository
     * @param  \StepUpDream\DreamAbilitySupport\Supports\File\FileOperation  $fileOperation
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     */
    public function __construct(
        protected MigrationRepository $migrationRepository,
        protected FileOperation $fileOperation,
        protected Resolver $resolver
    ) {
    }

    /**
     * Migrations execution.
     *
     * Do not perform migration of revisions that have been executed once in production
     *
     * @param  string  $migrationDirectoryPath
     */
    public function migration(string $migrationDirectoryPath): void
    {
        $version = basename($migrationDirectoryPath);
        $files = $this->fileOperation->allFiles($migrationDirectoryPath);

        //
        (new Info($this->output))->render(sprintf('Version %s : running migration', $version));

        foreach ($files as $file) {
            $basename = basename($file->getRealPath());
            (new Task($this->output))->render($basename, fn () => $this->runUp($version, $file));
        }

        $this->output->newLine();
    }

    /**
     * Get the name of the migration.
     *
     * @param  string  $path
     * @return string
     */
    protected function getMigrationName(string $path): string
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Run "up" a migration instance.
     *
     * @param  string  $revision
     * @param  \Symfony\Component\Finder\SplFileInfo  $file
     * @return string
     *
     * @see \Illuminate\Database\Migrations\Migrator::runUp()
     */
    protected function runUp(string $revision, SplFileInfo $file): string
    {
        $migration = File::requireOnce($file->getRealPath());

        if (empty($migration->getConnection())) {
            throw new LogicException("Specify 'connection' in the property. : ".$file->getRealPath());
        }

        $connection = $this->resolveConnection($migration->getConnection());

        if (! method_exists($migration, 'up')) {
            throw new LogicException("Specify 'up' in the method. : ".$file->getRealPath());
        }

        $callback = function () use ($migration) {
            $migration->up();
        };

        $this->getSchemaGrammar($connection)->supportsSchemaTransactions() && $migration->withinTransaction
            ? $connection->transaction($callback)
            : $callback();

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
}
