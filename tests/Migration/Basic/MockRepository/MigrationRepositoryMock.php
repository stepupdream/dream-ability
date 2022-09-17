<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Test\Migration\Basic\MockRepository;

use Carbon\Carbon;
use StepUpDream\DreamAbility\Database\Migrations\Basic\MigrationRepositoryInterface;
use StepUpDream\DreamAbility\Test\Migration\BaseMigrationRepositoryMock;

class MigrationRepositoryMock extends BaseMigrationRepositoryMock implements MigrationRepositoryInterface
{
    /**
     * @param  mixed[]  $initialData
     */
    public function __construct(array $initialData = [])
    {
        $this->createRepository();
        $this->table = $initialData;
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository(): void
    {
        $this->tableKey = ['id', 'table_name', 'version', 'created_at'];
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

        $this->table[] = $record;
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
        $result = $this->table()
            ->where('table_name', $tableName)
            ->sortByDesc('version')
            ->first();

        return $result['version'] ?? null;
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
}
