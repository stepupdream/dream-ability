<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Migrations;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Query\Builder;

abstract class BaseMigrationRepository
{
    /**
     * Create a new database migration repository instance.
     *
     * @param  string  $connectionName
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string  $tableName
     */
    public function __construct(
        protected string $connectionName,
        protected Resolver $resolver,
        protected string $tableName
    ) {
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection(): ConnectionInterface
    {
        return $this->resolver->connection($this->connectionName);
    }

    /**
     * Determine if the migration repository exists.
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

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    abstract public function createRepository(): void;
}
