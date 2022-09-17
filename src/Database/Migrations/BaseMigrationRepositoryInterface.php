<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Migrations;

use Illuminate\Database\Connection;

interface BaseMigrationRepositoryInterface
{
    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection(): Connection;

    /**
     * Determine if the migration table exists.
     *
     * @return bool
     */
    public function repositoryExists(): bool;

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository(): void;
}
