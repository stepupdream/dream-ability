<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Test\Migration;

use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Collection;
use StepUpDream\DreamAbility\Database\Migrations\BaseMigrationRepositoryInterface;

abstract class BaseMigrationRepositoryMock implements BaseMigrationRepositoryInterface
{
    /**
     * column information.
     * A variable to be treated as a substitute for the table.
     * The presence of data in this value shall indicate that the table has been created
     *
     * @var string[]
     */
    protected array $tableKey;

    /**
     * A variable to be treated as a substitute for the table.
     *
     * @var mixed
     */
    protected array $table = [];

    /**
     * Get data contained in a table.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function table(): Collection
    {
        return collect($this->table);
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param  string[]  $columns
     * @return bool
     */
    public function hasColumns(array $columns): bool
    {
        return in_array($columns, $this->tableKey, true);
    }

    /**
     * Determine if the migration table exists.
     *
     * @return bool
     */
    public function repositoryExists(): bool
    {
        return isset($this->tableKey);
    }

    /**
     * Get all data contained in the table.
     *
     * @return mixed[]
     */
    public function get(): array
    {
        return $this->table()->all();
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\MySqlConnection
     */
    public function getConnection(): MySqlConnection
    {
        return new MySqlConnection(fn () => []);
    }
}
