<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbility\Database\Migrations;

use LogicException;

/**
 * @method up()
 *
 * @see \Illuminate\Database\Migrations\Migration
 */
abstract class Migration
{
    /**
     * Enables, if supported, wrapping the migration within a transaction.
     *
     * @var bool
     */
    public bool $withinTransaction = true;

    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected string $connection;

    /**
     * Table Name.
     *
     * @var string
     */
    protected string $tableName;

    /**
     * Get the migration connection name.
     *
     * @return string
     */
    public function connectionName(): string
    {
        if (empty($this->connection)) {
            throw new LogicException("Specify 'connection' in the property.");
        }

        return $this->connection;
    }

    /**
     * Get table name.
     *
     * @return string
     */
    public function tableName(): string
    {
        if (empty($this->tableName)) {
            throw new LogicException("Specify 'tableName' in the property.");
        }

        return $this->tableName;
    }
}
