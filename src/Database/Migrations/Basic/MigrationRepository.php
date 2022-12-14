<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Migrations\Basic;

use Carbon\Carbon;
use StepUpDream\DreamAbility\Database\Migrations\BaseMigrationRepository;

class MigrationRepository extends BaseMigrationRepository implements MigrationRepositoryInterface
{
    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository(): void
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        if (! $schema->hasTable($this->tableName)) {
            $schema->create($this->tableName, function ($table) {
                $table->bigIncrements('id');
                $table->string('table_name');
                $table->string('version');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Insert data.
     *
     * @param  string  $tableName
     * @param  string  $version
     * @return void
     */
    public function insert(string $tableName, string $version): void
    {
        $timestamp = Carbon::now();
        $record = [
            'table_name' => $tableName,
            'version'    => $version,
            'created_at' => $timestamp,
        ];

        $this->table()->insert($record);
    }

    /**
     * Max version.
     *
     * @return string
     */
    public function maxVersion(): string
    {
        return $this->table()->max('version') ?? '';
    }

    /**
     * Get version by table name.
     *
     * @param  string  $tableName
     * @return string|null
     */
    public function findVersionByTableName(string $tableName): ?string
    {
        return $this->table()->where('table_name', $tableName)->orderByDesc('version')->first()?->version;
    }

    /**
     * Whether the data for the specified target exists or not.
     *
     * @param  string  $tableName
     * @param  string  $version
     * @return bool
     */
    public function existTargetVersion(string $tableName, string $version): bool
    {
        $result = $this->table()
            ->where('table_name', $tableName)
            ->where('version', $version)
            ->first();

        return empty($result);
    }

    /**
     * Get table name by version.
     *
     * @param  string  $version
     * @return string[]
     */
    public function getTableNameByVersion(string $version): array
    {
        return $this->table()->where('version', $version)->pluck('table_name')->all();
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param  string[]  $columns
     * @return bool
     */
    public function hasColumns(array $columns): bool
    {
        return $this->getConnection()->getSchemaBuilder()->hasColumns($this->tableName, $columns);
    }
}
