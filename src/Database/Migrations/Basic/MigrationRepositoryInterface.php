<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Migrations\Basic;

use StepUpDream\DreamAbility\Database\Migrations\BaseMigrationRepositoryInterface;

interface MigrationRepositoryInterface extends BaseMigrationRepositoryInterface
{
    /**
     * Insert data.
     *
     * @param  string  $tableName
     * @param  string  $version
     * @return void
     */
    public function insert(string $tableName, string $version): void;

    /**
     * Max version.
     *
     * @return string
     */
    public function maxVersion(): string;

    /**
     * Get version by table name.
     *
     * @param  string  $tableName
     * @return string|null
     */
    public function findVersionByTableName(string $tableName): ?string;

    /**
     * Whether the data for the specified target exists or not.
     *
     * @param  string  $tableName
     * @param  string  $version
     * @return bool
     */
    public function existTargetVersion(string $tableName, string $version): bool;

    /**
     * Get table name by version.
     *
     * @param  string  $version
     * @return string[]
     */
    public function getTableNameByVersion(string $version): array;

    /**
     * Determine if the given table has given columns.
     *
     * @param  string[]  $columns
     * @return bool
     */
    public function hasColumns(array $columns): bool;
}
