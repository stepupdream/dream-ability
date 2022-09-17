<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Migrations;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;

abstract class BaseMigrationRepository implements BaseMigrationRepositoryInterface
{
    /**
     * Create a new database migration repository instance.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Database\DatabaseManager  $databaseManager
     * @param  string  $tableName
     */
    public function __construct(
        protected string $connectionName,
        protected DatabaseManager $databaseManager,
        protected string $tableName
    ) {
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection(): Connection
    {
        return $this->databaseManager->connection($this->connectionName);
    }

    /**
     * Determine if the migration table exists.
     *
     * @return bool
     */
    public function repositoryExists(): bool
    {
        return $this->getConnection()->getSchemaBuilder()->hasTable($this->tableName);
    }

    /**
     * Get a query builder for the migration table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table(): Builder
    {
        return $this->getConnection()->table($this->tableName)->useWritePdo();
    }
}
